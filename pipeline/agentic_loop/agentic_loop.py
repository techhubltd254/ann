#!/usr/bin/env python3
"""Agentic Loop — autonomous platform optimisation.

Observe -> Decide -> Act -> Learn cycle that runs on a schedule.
"""

import json
import logging
import sys
import time
from datetime import datetime
from pathlib import Path

from observer import Observer
from decider import Decider
from actor import Actor
from learner import Learner

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
)
logger = logging.getLogger("agentic_loop")


class AgenticLoop:
    """Autonomous feedback loop for platform self-optimisation."""

    def __init__(self, config_path: str = "agentic_loop_config.json"):
        self.base_dir = Path(__file__).parent
        self.data_dir = self.base_dir / "data"
        self.data_dir.mkdir(parents=True, exist_ok=True)

        with open(self.base_dir / config_path) as f:
            self.config = json.load(f)

        self.observer = Observer(self.config.get("observation", {}), str(self.data_dir))
        self.decider = Decider(self.config, str(self.data_dir))
        self.actor = Actor(str(self.data_dir))
        self.learner = Learner(self.config, str(self.data_dir))

        self.cycle_count = 0

    def run_cycle(self) -> dict:
        """Execute one complete Observe -> Decide -> Act -> Learn cycle."""
        self.cycle_count += 1
        cycle_id = f"cycle_{self.cycle_count}_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        logger.info(f"=== Starting {cycle_id} ===")

        cycle_result = {
            "cycle_id": cycle_id,
            "started_at": datetime.now().isoformat(),
            "steps": [],
        }

        # Step 1: Observe
        logger.info("OBSERVE -- gathering signals...")
        snapshot = self.observer.observe()
        cycle_result["steps"].append({"step": "observe", "timestamp": snapshot["timestamp"]})
        logger.info(f"Observed: SEO={snapshot.get('seo', {}).get('trending_keywords', [])}")

        # Step 2: Decide
        logger.info("DECIDE -- analysing snapshot...")
        decisions = self.decider.decide(snapshot)
        cycle_result["steps"].append({
            "step": "decide",
            "decisions": [d["action"] for d in decisions],
        })
        logger.info(f"Decisions: {[d['action'] for d in decisions]}")

        # Step 3: Act
        logger.info("ACT -- executing decisions...")
        outcomes = []
        for decision in decisions:
            logger.info(f"  -> Executing {decision['action']}...")
            outcome = self.actor.execute(decision, snapshot)
            outcomes.append(outcome)
            logger.info(f"  -> {decision['action']}: {outcome['status']}")

        cycle_result["steps"].append({"step": "act", "outcomes": outcomes})

        # Step 4: Learn
        logger.info("LEARN -- analysing outcomes...")
        recent_outcomes = list(outcomes)
        action_log_path = self.data_dir / "action_log.jsonl"
        if action_log_path.exists():
            with open(action_log_path) as f:
                for line in f.readlines()[-20:]:
                    try:
                        recent_outcomes.append(json.loads(line))
                    except Exception:
                        pass

        learning_result = self.learner.learn([snapshot], recent_outcomes)
        cycle_result["steps"].append({"step": "learn", "result": learning_result})

        advice = self.learner.advise(snapshot)
        cycle_result["advice"] = advice

        cycle_result["completed_at"] = datetime.now().isoformat()
        start = datetime.fromisoformat(cycle_result["started_at"])
        end = datetime.fromisoformat(cycle_result["completed_at"])
        cycle_result["duration_s"] = (end - start).total_seconds()

        logger.info(f"=== {cycle_id} complete in {cycle_result['duration_s']:.1f}s ===")
        if advice:
            logger.info(f"Past advice applicable: {len(advice)} items")

        self._save_cycle_result(cycle_result)
        return cycle_result

    def run_forever(self, interval_minutes=None):
        interval = interval_minutes or self.config.get("loop", {}).get("interval_minutes", 60)
        logger.info(f"Agentic Loop starting -- interval={interval}min")
        logger.info("Press Ctrl+C to stop")
        try:
            while True:
                self.run_cycle()
                logger.info(f"Sleeping for {interval} minutes...")
                time.sleep(interval * 60)
        except KeyboardInterrupt:
            logger.info("Agentic Loop stopped by user")

    def _save_cycle_result(self, result: dict):
        path = self.data_dir / f"cycle_{result['cycle_id']}.json"
        with open(path, "w") as f:
            json.dump(result, f, indent=2, default=str)
        cycles = sorted(self.data_dir.glob("cycle_*.json"))
        for old in cycles[:-50]:
            old.unlink()


if __name__ == "__main__":
    loop = AgenticLoop()
    if "--once" in sys.argv:
        result = loop.run_cycle()
        print(json.dumps(result, indent=2, default=str))
    elif "--forever" in sys.argv:
        loop.run_forever()
    else:
        print("Usage: python agentic_loop.py --once")
        print("       python agentic_loop.py --forever")
