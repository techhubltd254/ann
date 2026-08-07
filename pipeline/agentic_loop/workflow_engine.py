#!/usr/bin/env python3
"""
Lightweight workflow engine that executes n8n-compatible workflow JSONs.
Reads workflow definitions from n8n-workflows/ and runs them on schedule.
"""
import json, os, sys, time, logging, hashlib, subprocess
from pathlib import Path
from datetime import datetime, timedelta
import urllib.request
import urllib.error

ROOT = Path(__file__).resolve().parent.parent
WORKFLOWS_DIR = ROOT / "n8n-workflows"
LOG_DIR = ROOT / "storage" / "logs"
os.makedirs(LOG_DIR, exist_ok=True)

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[
        logging.FileHandler(LOG_DIR / "workflow-engine.log"),
        logging.StreamHandler(),
    ],
)
log = logging.getLogger("workflow-engine")

class WorkflowEngine:
    def __init__(self):
        self.workflows = {}
        self.last_run = {}
        self.api_base = "http://localhost:8080/api"
        self.api_token = os.environ.get("API_TOKEN", "")

    def load_workflows(self):
        for f in WORKFLOWS_DIR.glob("*.json"):
            try:
                data = json.loads(f.read_text())
                name = data.get("name", f.stem)
                self.workflows[name] = data
                log.info(f"Loaded workflow: {name}")
            except Exception as e:
                log.error(f"Failed to load {f}: {e}")

    def get_schedule(self, workflow):
        """Extract schedule from workflow nodes."""
        for node in workflow.get("nodes", []):
            if node["type"] == "n8n-nodes-base.scheduleTrigger":
                rule = node.get("parameters", {}).get("rule", {})
                interval = rule.get("interval", [{}])[0]
                field = interval.get("field", "hours")
                value = interval.get("hoursInterval", 168)
                return {"field": field, "value": value}
        return None

    def should_run(self, name, schedule):
        if name not in self.last_run:
            return True
        last = self.last_run[name]
        now = datetime.now()
        field = schedule.get("field", "hours")
        value = schedule.get("value", 24)
        if field == "hours":
            return (now - last).total_seconds() >= value * 3600
        elif field == "weeks":
            return (now - last).total_seconds() >= value * 7 * 86400
        elif field == "minutes":
            return (now - last).total_seconds() >= value * 60
        return True

    def api_request(self, method, path, data=None):
        url = f"{self.api_base}{path}"
        headers = {
            "Content-Type": "application/json",
        }
        if self.api_token:
            headers["Authorization"] = f"Bearer {self.api_token}"
        body = json.dumps(data).encode() if data else None
        req = urllib.request.Request(url, data=body, headers=headers, method=method)
        try:
            with urllib.request.urlopen(req, timeout=30) as resp:
                return json.loads(resp.read())
        except Exception as e:
            log.error(f"API {method} {path}: {e}")
            return None

    def execute_node_function(self, code, input_data):
        """Execute a Function node's JavaScript code (simplified)."""
        exec_globals = {
            "$input": lambda: input_data,
            "$json": input_data.get("json", {}),
            "$:": lambda x: x,
        }
        try:
            exec(code, exec_globals)
            return exec_globals.get("return_value", input_data)
        except Exception as e:
            log.error(f"Function execution failed: {e}")
            return input_data

    def run_workflow(self, name, workflow):
        log.info(f"Running workflow: {name}")
        nodes = {n["name"]: n for n in workflow.get("nodes", [])}
        connections = workflow.get("connections", {})

        # Find start node (webhook or schedule)
        start_node = None
        for n in workflow["nodes"]:
            if n["name"] not in connections:
                continue
            if n["type"] in ("n8n-nodes-base.webhook", "n8n-nodes-base.scheduleTrigger"):
                start_node = n
                break

        if not start_node:
            log.warning(f"No start node found in {name}")
            return

        # Simple linear execution following connections
        current = start_node
        input_data = {"json": {"triggered_at": datetime.now().isoformat()}}

        visited = set()
        while current and current["name"] not in visited:
            visited.add(current["name"])
            node_type = current["type"]
            log.info(f"  Executing: {current['name']} ({node_type})")

            if node_type == "n8n-nodes-base.function":
                code = current.get("parameters", {}).get("functionCode", "")
                input_data = self.execute_node_function(code, input_data)

            elif node_type == "n8n-nodes-base.httpRequest":
                params = current.get("parameters", {})
                method = params.get("method", "GET")
                url = params.get("url", "")
                input_data = self.api_request(method, url) or input_data

            elif node_type == "n8n-nodes-base.emailSend":
                params = current.get("parameters", {})
                log.info(f"    Would send email: {params.get('subject', 'No subject')}")

            elif node_type == "n8n-nodes-base.openAi":
                log.info("    Would call OpenAI API")

            # Follow connection to next node
            conn = connections.get(current["name"], {})
            main_conns = conn.get("main", [])
            next_nodes = []
            for conn_group in main_conns:
                for c in conn_group:
                    next_name = c.get("node")
                    if next_name and next_name in nodes:
                        next_nodes.append(nodes[next_name])

            current = next_nodes[0] if next_nodes else None

        self.last_run[name] = datetime.now()
        log.info(f"Workflow {name} completed")

    def run_cycle(self):
        for name, workflow in self.workflows.items():
            schedule = self.get_schedule(workflow)
            if schedule:
                if self.should_run(name, schedule):
                    try:
                        self.run_workflow(name, workflow)
                    except Exception as e:
                        log.error(f"Workflow {name} failed: {e}")
            else:
                log.debug(f"Skipping {name}: no schedule")

    def run_forever(self, interval=60):
        log.info(f"Workflow engine started (check interval: {interval}s)")
        while True:
            self.run_cycle()
            time.sleep(interval)

def main():
    engine = WorkflowEngine()
    engine.load_workflows()
    engine.run_forever()

if __name__ == "__main__":
    main()
