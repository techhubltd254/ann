"""Decider — analyses observations and chooses actions using Kimi K3 or rule fallback."""

import json
import logging
import os
from datetime import datetime, timedelta
from pathlib import Path
from typing import Optional

import requests

logger = logging.getLogger(__name__)


class Decider:
    """Analyses the current state and decides which actions to take."""

    AVAILABLE_ACTIONS = ["seo_tune", "recommendation_refresh", "pipeline_tune", "content_promote", "cache_warm"]

    def __init__(self, config: dict, data_dir: str = "agentic_data"):
        self.full_config = config
        self.config = config.get("decider", {})
        self.actions_config = config.get("actions", {})
        self.loop_config = config.get("loop", {})
        self.data_dir = Path(data_dir)
        self.data_dir.mkdir(parents=True, exist_ok=True)
        self.api_key = os.getenv("OPENROUTER_API_KEY", "")
        self.decision_log = self._load_decision_log()

    def decide(self, snapshot: dict) -> list[dict]:
        """Given the current observation snapshot, return ranked action decisions."""
        now = datetime.now()

        # Check cooldowns
        cooled_actions = self._filter_cooldown(now)

        if self.config.get("fallback_to_rules", True):
            decisions = self._rule_based_decide(snapshot, cooled_actions)
        else:
            decisions = self._ai_decide(snapshot, cooled_actions)

        # If AI is available and we have a key, augment with AI insights
        if self.api_key:
            ai_insights = self._ai_augment(snapshot, decisions)
            for d in decisions:
                d["ai_insight"] = ai_insights.get(d["action"], "")

        self._log_decisions(decisions)
        return decisions

    def _rule_based_decide(self, snapshot: dict, available: list[str]) -> list[dict]:
        decisions = []
        obs = snapshot.get("seo", {})
        pipe = snapshot.get("pipeline", {})
        weather = snapshot.get("weather", {})

        # Rule 1: SEO tune if trends show shift
        if "seo_tune" in available:
            trends = obs.get("trending_keywords", [])
            up_trends = [t for t in trends if t.get("trend_direction") == "up" and t.get("current_interest", 0) > 30]
            if len(up_trends) >= 2:
                decisions.append({
                    "action": "seo_tune",
                    "priority": self.actions_config["seo_tune"]["priority"],
                    "reason": f"Google Trends shift: {[t['keyword'] for t in up_trends[:3]]} trending up",
                    "trigger": "seo_trend_detected",
                    "confidence": 0.8,
                })

        # Rule 2: Recommendation refresh if weather changed or monthly change
        if "recommendation_refresh" in available:
            decisions.append({
                "action": "recommendation_refresh",
                "priority": self.actions_config["recommendation_refresh"]["priority"],
                "reason": f"Scheduled refresh (month {snapshot.get('weather', {}).get('month', datetime.now().month)})",
                "trigger": "monthly_refresh",
                "confidence": 0.9,
            })

        # Rule 3: Pipeline tune if quality dropped
        if "pipeline_tune" in available:
            quality = pipe.get("avg_quality", 1.0)
            if 0 < quality < self.config.get("pipeline", {}).get("quality_threshold", 0.7):
                decisions.append({
                    "action": "pipeline_tune",
                    "priority": self.actions_config["pipeline_tune"]["priority"],
                    "reason": f"Pipeline quality dropped to {quality:.2f} (threshold: {self.config.get('pipeline', {}).get('quality_threshold', 0.7)})",
                    "trigger": "quality_degradation",
                    "confidence": 0.85,
                })

        # Rule 4: Cache warm always (low priority)
        if "cache_warm" in available:
            decisions.append({
                "action": "cache_warm",
                "priority": self.actions_config["cache_warm"]["priority"],
                "reason": "Routine cache pre-warm",
                "trigger": "scheduled",
                "confidence": 0.95,
            })

        # Sort by priority descending, take max_actions_per_cycle
        max_actions = self.loop_config.get("max_actions_per_cycle", 3)
        decisions.sort(key=lambda d: d["priority"], reverse=True)
        return decisions[:max_actions]

    def _ai_decide(self, snapshot: dict, available: list[str]) -> list[dict]:
        """Use Kimi K3 for intelligent decision-making."""
        if not self.api_key:
            return self._rule_based_decide(snapshot, available)

        prompt = f"""You are the AI brain of the KICC National Exhibition Platform. 
Current system snapshot:
{json.dumps(snapshot, indent=2, default=str)}

Available actions: {available}

Return JSON array of decisions with: action, priority (1-10), reason, trigger, confidence (0-1).
Rule: max {self.config.get('loop', {}).get('max_actions_per_cycle', 3)} actions.
Prioritise actions that improve user experience and SEO performance.
"""

        try:
            resp = requests.post(
                "https://openrouter.ai/api/v1/chat/completions",
                headers={
                    "Authorization": f"Bearer {self.api_key}",
                    "Content-Type": "application/json",
                },
                json={
                    "model": self.config.get("model", "moonshotai/kimi-k3-mini"),
                    "messages": [
                        {"role": "system", "content": "You are a system architect that returns ONLY valid JSON arrays."},
                        {"role": "user", "content": prompt},
                    ],
                    "temperature": self.config.get("temperature", 0.2),
                    "max_tokens": 1000,
                },
                timeout=30,
            )
            content = resp.json()["choices"][0]["message"]["content"]
            start = content.find("[")
            end = content.rfind("]")
            if start != -1 and end != -1:
                decisions = json.loads(content[start : end + 1])
                return [d for d in decisions if d.get("action") in self.AVAILABLE_ACTIONS]
        except Exception as e:
            logger.warning(f"AI decision failed: {e}")

        return self._rule_based_decide(snapshot, available)

    def _ai_augment(self, snapshot: dict, decisions: list[dict]) -> dict:
        """Add AI-generated insight text to each decision."""
        insights = {}
        for d in decisions:
            insights[d["action"]] = f"AI reasoning: {d['trigger']} — confidence {d.get('confidence', 0.5):.0%}"
        return insights

    def _filter_cooldown(self, now: datetime) -> list[str]:
        """Return actions not in cooldown."""
        cooled = []
        for action in self.AVAILABLE_ACTIONS:
            cooldown_hours = self.actions_config.get(action, {}).get("cooldown_hours", 24)
            last_run = self.decision_log.get(action, {}).get("last_run")
            if last_run:
                last = datetime.fromisoformat(last_run)
                if now - last < timedelta(hours=cooldown_hours):
                    continue
            cooled.append(action)
        return cooled

    def _load_decision_log(self) -> dict:
        path = self.data_dir / "decision_log.json"
        if path.exists():
            with open(path) as f:
                return json.load(f)
        return {}

    def _log_decisions(self, decisions: list[dict]):
        now = datetime.now().isoformat()
        for d in decisions:
            self.decision_log[d["action"]] = {
                "last_run": now,
                "last_reason": d.get("reason", ""),
                "last_trigger": d.get("trigger", ""),
                "count": self.decision_log.get(d["action"], {}).get("count", 0) + 1,
            }
        path = self.data_dir / "decision_log.json"
        with open(path, "w") as f:
            json.dump(self.decision_log, f, indent=2)
