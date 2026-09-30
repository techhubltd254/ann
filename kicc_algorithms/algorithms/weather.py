"""Algorithm 19: Weather-station ingestion job.

Reads station observations (injectable feed port), upserts the
SeasonalCalendar equivalent per county-month, invalidates the display
cache - completing the loop that production left unwired.
"""
from __future__ import annotations
from collections import defaultdict


class WeatherIngestor:
    def __init__(self, repo, feed, cache):
        self.repo, self.feed, self.cache = repo, feed, cache

    async def run(self) -> dict:
        observations = await self.feed.fetch()
        by_key: dict[tuple, list] = defaultdict(list)
        for obs in observations:
            by_key[(obs["county_code"], obs["month"])].append(obs)
        upserted = 0
        for (county_code, month), rows in by_key.items():
            avg_temp = sum(r["temp_c"] for r in rows) / len(rows)
            rainfall = sum(r["rainfall_mm"] for r in rows)
            season = "rainy" if rainfall > 150 else "dry"
            await self.repo.upsert("seasonal_calendar", ["county_code", "month"], {
                "county_code": county_code, "month": month,
                "avg_temp_c": round(avg_temp, 1), "rainfall_mm": round(rainfall, 1),
                "season_tag": season,
            })
            upserted += 1
        await self.cache.invalidate("platform_stats")
        return {"observations": len(observations), "calendar_rows_upserted": upserted}
