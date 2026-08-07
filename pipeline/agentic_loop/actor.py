"""Actor — executes decisions made by the Decider."""

import json
import logging
import subprocess
import sys
from datetime import datetime
from pathlib import Path
from typing import Optional

logger = logging.getLogger(__name__)


class Actor:
    """Executes actions: SEO tune, recommendation refresh, pipeline tune, etc."""

    def __init__(self, data_dir: str = "agentic_data"):
        self.data_dir = Path(data_dir)
        self.data_dir.mkdir(parents=True, exist_ok=True)
        self.action_log = self.data_dir / "action_log.jsonl"

    def execute(self, decision: dict, snapshot: dict) -> dict:
        action = decision["action"]
        logger.info(f"Executing action: {action}")
        result = {
            "action": action,
            "trigger": decision.get("trigger", ""),
            "reason": decision.get("reason", ""),
            "started_at": datetime.now().isoformat(),
            "status": "running",
        }

        try:
            handler = getattr(self, f"_do_{action}", None)
            if handler:
                output = handler(snapshot)
                result.update(output)
                result["status"] = "completed"
            else:
                result["status"] = "skipped"
                result["message"] = f"No handler for {action}"
        except Exception as e:
            result["status"] = "failed"
            result["error"] = str(e)
            logger.error(f"Action {action} failed: {e}")

        result["completed_at"] = datetime.now().isoformat()
        self._log_action(result)
        return result

    def _do_seo_tune(self, snapshot: dict) -> dict:
        """Adjust SEO meta and content strategy based on trends."""
        trends = snapshot.get("seo", {}).get("trending_keywords", [])
        regional = snapshot.get("seo", {}).get("regional_interest", [])

        hot_keywords = [t["keyword"] for t in trends if t.get("trend_direction") == "up"]
        cold_keywords = [t["keyword"] for t in trends if t.get("trend_direction") == "down"]

        # Write SEO tuning instructions to a file that Laravel can pick up
        seo_instr = {
            "updated_at": datetime.now().isoformat(),
            "boost_keywords": hot_keywords,
            "demote_keywords": cold_keywords,
            "regional_focus": regional,
            "action": "seo_tune",
        }

        path = self.data_dir / "seo_instructions.json"
        with open(path, "w") as f:
            json.dump(seo_instr, f, indent=2)

        return {
            "hot_keywords": hot_keywords,
            "cold_keywords": cold_keywords,
            "instructions_written": str(path),
        }

    def _do_recommendation_refresh(self, snapshot: dict) -> dict:
        """Refresh destination recommendations based on current weather and trends."""
        weather = snapshot.get("weather", {})
        current_month = weather.get("month", datetime.now().month)

        # Generate updated recommendation weights for the DestinationMatcher
        rec_data = {
            "updated_at": datetime.now().isoformat(),
            "month": current_month,
            "season": "dry" if current_month in [1, 2, 6, 7, 8, 9] else "wet",
            "action": "recommendation_refresh",
        }

        path = self.data_dir / "recommendation_weights.json"
        with open(path, "w") as f:
            json.dump(rec_data, f, indent=2)

        return {"month": current_month, "weights_written": str(path)}

    def _do_pipeline_tune(self, snapshot: dict) -> dict:
        """Adjust pipeline parameters based on quality metrics."""
        pipe = snapshot.get("pipeline", {})
        quality = pipe.get("avg_quality", 0.8)

        # Tune depth strength inversely to quality
        new_strength = max(0.5, min(3.0, 1.5 + (0.7 - quality) * 2))

        tune_data = {
            "updated_at": datetime.now().isoformat(),
            "depth_strength": round(new_strength, 2),
            "convergence": round(max(0.1, 0.3 - (quality - 0.7) * 0.5), 2),
            "action": "pipeline_tune",
        }

        path = self.data_dir / "pipeline_params.json"
        with open(path, "w") as f:
            json.dump(tune_data, f, indent=2)

        return {
            "previous_quality": quality,
            "new_depth_strength": tune_data["depth_strength"],
            "new_convergence": tune_data["convergence"],
        }

    def _do_content_promote(self, snapshot: dict) -> dict:
        """Identify top-performing content for promotion."""
        trends = snapshot.get("seo", {}).get("trending_keywords", [])
        hot = [t["keyword"] for t in trends if t.get("trend_direction") == "up"][:5]

        promo_data = {
            "updated_at": datetime.now().isoformat(),
            "promote_keywords": hot,
            "action": "content_promote",
        }

        path = self.data_dir / "promo_plan.json"
        with open(path, "w") as f:
            json.dump(promo_data, f, indent=2)

        return {"promoted_keywords": hot}

    def _do_cache_warm(self, snapshot: dict) -> dict:
        """Pre-warm caches for likely next requests."""
        trends = snapshot.get("seo", {}).get("trending_keywords", [])
        hot = [t["keyword"] for t in trends if t.get("trend_direction") == "up"][:3]

        cache_plan = {
            "updated_at": datetime.now().isoformat(),
            "prewarm_for_keywords": hot,
            "endpoints": ["/api/counties", "/api/national-hub"],
            "action": "cache_warm",
        }

        path = self.data_dir / "cache_plan.json"
        with open(path, "w") as f:
            json.dump(cache_plan, f, indent=2)

        return {"prewarmed_routes": cache_plan["endpoints"]}

    def _log_action(self, result: dict):
        with open(self.action_log, "a") as f:
            f.write(json.dumps(result) + "\n")
