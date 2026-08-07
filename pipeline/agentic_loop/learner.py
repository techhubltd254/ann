"""Learner — analyses past outcomes and improves future decisions."""

import json
import logging
from collections import defaultdict
from datetime import datetime, timedelta
from pathlib import Path
from typing import Any

logger = logging.getLogger(__name__)


class Learner:
    """Analyses action outcomes, updates decision weights for reinforcement."""

    def __init__(self, config: dict, data_dir: str = "agentic_data"):
        self.full_config = config
        self.config = config.get("learning", {})
        self.data_dir = Path(data_dir)
        self.knowledge_base = self._load_knowledge()

    def learn(self, recent_snapshots: list[dict], recent_outcomes: list[dict]) -> dict:
        """Process recent outcomes and update the knowledge base."""
        now = datetime.now()
        window_days = self.config.get("learning", {}).get("feedback_window_days", 7)
        cutoff = now - timedelta(days=window_days)

        relevant = [
            o for o in recent_outcomes
            if datetime.fromisoformat(o.get("completed_at", "2000-01-01")) > cutoff
        ]

        if len(relevant) < self.config.get("learning", {}).get("min_outcomes_for_learning", 5):
            return {"status": "insufficient_data", "samples": len(relevant)}

        lessons = self._extract_lessons(relevant)
        self._update_knowledge(lessons)
        self._save_knowledge()

        return {
            "status": "learned",
            "samples_analysed": len(relevant),
            "lessons_extracted": len(lessons),
            "knowledge_size": len(self.knowledge_base.get("lessons", [])),
        }

    def advise(self, snapshot: dict) -> list[dict]:
        """Return relevant advice from past learnings for the current context."""
        advice = []
        month = snapshot.get("weather", {}).get("month", datetime.now().month)
        trends = snapshot.get("seo", {}).get("trending_keywords", [])

        for lesson in self.knowledge_base.get("lessons", []):
            score = 0
            context = lesson.get("context", {})

            if context.get("month") == month:
                score += 20
            if any(t["keyword"] in lesson.get("keywords", []) for t in trends):
                score += 15

            if score > 10:
                advice.append({
                    "lesson": lesson["summary"],
                    "relevance_score": score,
                    "action": lesson.get("action_taken"),
                    "outcome": lesson.get("outcome"),
                })

        advice.sort(key=lambda a: a["relevance_score"], reverse=True)
        return advice[:5]

    def _extract_lessons(self, outcomes: list[dict]) -> list[dict]:
        lessons = []
        action_groups = defaultdict(list)

        for o in outcomes:
            action_groups[o.get("action", "unknown")].append(o)

        for action, group in action_groups.items():
            success_rate = sum(1 for o in group if o.get("status") == "completed") / len(group)

            if success_rate < 0.5 and len(group) >= 3:
                lessons.append({
                    "summary": f"Action '{action}' has low success rate ({success_rate:.0%} over {len(group)} runs)",
                    "context": {"action": action},
                    "action_taken": action,
                    "outcome": "failure",
                    "recommendation": f"Review {action} implementation",
                    "confidence": min(0.9, success_rate + 0.5),
                })
            elif success_rate > 0.8 and len(group) >= 3:
                lessons.append({
                    "summary": f"Action '{action}' is reliable ({success_rate:.0%} over {len(group)} runs)",
                    "context": {"action": action},
                    "action_taken": action,
                    "outcome": "success",
                    "recommendation": f"Increase frequency of {action}",
                    "confidence": min(0.95, success_rate + 0.1),
                })

        return lessons

    def _update_knowledge(self, lessons: list[dict]):
        rate = self.config.get("learning", {}).get("reinforcement_rate", 0.15)

        for lesson in lessons:
            existing = None
            for i, l in enumerate(self.knowledge_base.get("lessons", [])):
                if l["summary"] == lesson["summary"]:
                    existing = i
                    break

            if existing is not None:
                self.knowledge_base["lessons"][existing]["confidence"] = min(
                    1.0,
                    self.knowledge_base["lessons"][existing]["confidence"] + rate,
                )
                self.knowledge_base["lessons"][existing]["last_seen"] = datetime.now().isoformat()
            else:
                lesson["created"] = datetime.now().isoformat()
                lesson["last_seen"] = lesson["created"]
                self.knowledge_base.setdefault("lessons", []).append(lesson)

        cutoff = datetime.now() - timedelta(days=30)
        self.knowledge_base["lessons"] = [
            l for l in self.knowledge_base.get("lessons", [])
            if l["confidence"] > 0.2
            and datetime.fromisoformat(l.get("last_seen", "2000-01-01")) > cutoff
        ]

    def _load_knowledge(self) -> dict:
        path = self.data_dir / "knowledge_base.json"
        if path.exists():
            with open(path) as f:
                return json.load(f)
        return {"version": 2, "lessons": [], "created": datetime.now().isoformat()}

    def _save_knowledge(self):
        path = self.data_dir / "knowledge_base.json"
        with open(path, "w") as f:
            json.dump(self.knowledge_base, f, indent=2)
