"""Observer — collects signals from SEO, pipeline, weather, and user behavior."""

import json
import logging
import os
import sqlite3
from datetime import datetime, timedelta
from pathlib import Path
from typing import Any

import requests

logger = logging.getLogger(__name__)


class Observer:
    """Gathers signals from all observables and returns a snapshot of current state."""

    def __init__(self, config: dict, data_dir: str = "agentic_data"):
        self.config = config
        self.data_dir = Path(data_dir)
        self.data_dir.mkdir(parents=True, exist_ok=True)

    def observe(self) -> dict:
        snapshot = {
            "timestamp": datetime.now().isoformat(),
            "seo": self._observe_seo(),
            "pipeline": self._observe_pipeline(),
            "weather": self._observe_weather(),
            "user_behavior": self._observe_user_behavior(),
            "system": self._observe_system(),
        }
        self._save_snapshot(snapshot)
        return snapshot

    def _observe_seo(self) -> dict:
        if not self.config.get("seo", {}).get("enabled", False):
            return {"status": "disabled"}

        signals = {
            "trending_keywords": self._fetch_google_trends(),
            "search_console_metrics": self._fetch_search_console(),
            "regional_interest": self._detect_regional_interest(),
        }
        return signals

    def _fetch_google_trends(self) -> list:
        try:
            from pytrends.request import TrendReq

            pytrends = TrendReq(hl="en-US", tz=180)
            keywords = self.config.get("seo", {}).get("google_trends_keywords", [])
            if not keywords:
                return []
            pytrends.build_payload(keywords, timeframe="now 7-d", geo="KE")
            data = pytrends.interest_over_time()
            if data.empty:
                return []

            trends = []
            for kw in keywords:
                if kw in data.columns:
                    trends.append({
                        "keyword": kw,
                        "current_interest": int(data[kw].iloc[-1]) if not data.empty else 0,
                        "trend_direction": "up" if len(data) > 1 and data[kw].iloc[-1] > data[kw].iloc[0] else "down",
                    })
            return trends
        except ImportError:
            logger.warning("pytrends not installed")
            return []
        except Exception as e:
            logger.warning(f"Google Trends fetch failed: {e}")
            return []

    def _fetch_search_console(self) -> dict:
        return {
            "clicks_7d": 0,
            "impressions_7d": 0,
            "avg_position": 0,
            "top_queries": [],
            "source": "placeholder",
        }

    def _detect_regional_interest(self) -> list:
        return [
            {"region": "US", "keywords": ["safari", "kenya beach"], "intent": "warm_escape"},
            {"region": "EU", "keywords": ["migration safari", "luxury kenya"], "intent": "adventure"},
            {"region": "KE", "keywords": ["exhibition nairobi", "county trade fair"], "intent": "business"},
        ]

    def _observe_pipeline(self) -> dict:
        if not self.config.get("pipeline", {}).get("enabled", False):
            return {"status": "disabled"}

        pipeline_dir = self.data_dir / "pipeline_logs"
        pipeline_dir.mkdir(parents=True, exist_ok=True)

        jobs = []
        for log_file in pipeline_dir.glob("*.json"):
            try:
                with open(log_file) as f:
                    job = json.load(f)
                    jobs.append(job)
            except Exception:
                continue

        recent = [j for j in jobs if datetime.fromisoformat(j.get("timestamp", "2000-01-01")) > datetime.now() - timedelta(days=7)]

        return {
            "total_jobs_7d": len(recent),
            "avg_latency_s": self._avg([j.get("latency_s", 0) for j in recent]),
            "avg_quality": self._avg([j.get("quality_score", 0) for j in recent]),
            "failure_rate": sum(1 for j in recent if j.get("status") == "failed") / max(len(recent), 1),
            "jobs": recent[-10:],
        }

    def _observe_weather(self) -> dict:
        if not self.config.get("weather", {}).get("enabled", False):
            return {"status": "disabled"}

        counties_path = Path(__file__).parent.parent / "data" / "counties.json"
        if not counties_path.exists():
            return {"status": "no_data"}

        with open(counties_path, encoding="utf-8") as f:
            counties = json.load(f)

        current_month = datetime.now().month
        weather_snapshot = []

        for cty in counties:
            weather_snapshot.append({
                "id": cty["id"],
                "name": cty["name"],
                "weather_station": cty.get("weather_station_id"),
                "lat": cty.get("latitude"),
                "lon": cty.get("longitude"),
                "primary_sectors": cty.get("primary_sectors", []),
            })

        return {
            "month": current_month,
            "counties_monitored": len(weather_snapshot),
            "sample": weather_snapshot[:5],
        }

    def _observe_user_behavior(self) -> dict:
        if not self.config.get("user_behavior", {}).get("enabled", False):
            return {"status": "disabled"}

        # Read from Laravel SQLite if available
        db_path = Path(__file__).parent.parent / "laravel-backend" / "database" / "database.sqlite"
        if not db_path.exists():
            return {"signals_count": 0, "source": "no_db"}

        try:
            conn = sqlite3.connect(str(db_path))
            cursor = conn.cursor()
            cursor.execute("SELECT COUNT(*) FROM users")
            user_count = cursor.fetchone()[0]
            conn.close()
            return {"signals_count": user_count, "users": user_count, "source": "sqlite"}
        except Exception as e:
            logger.warning(f"DB read failed: {e}")
            return {"signals_count": 0, "source": "error"}

    def _observe_system(self) -> dict:
        import psutil
        try:
            return {
                "cpu_percent": psutil.cpu_percent(interval=0.5),
                "memory_percent": psutil.virtual_memory().percent,
                "disk_percent": psutil.disk_usage("/").percent,
            }
        except ImportError:
            return {"cpu": "n/a", "memory": "n/a"}

    def _save_snapshot(self, snapshot: dict):
        path = self.data_dir / f"snapshot_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
        with open(path, "w") as f:
            json.dump(snapshot, f, indent=2, default=str)

        # Keep only last 100 snapshots
        snapshots = sorted(self.data_dir.glob("snapshot_*.json"))
        for old in snapshots[:-100]:
            old.unlink()

    @staticmethod
    def _avg(values: list) -> float:
        return sum(values) / len(values) if values else 0.0
