"""Algorithm 9: BuildRecommendations - content-based recommender.

Scores county/sector affinity from a user's purchase + view history with
seasonality boost from the SeasonalCalendar. Deterministic, explainable,
O(history) per request with cached popularity priors.
"""
from __future__ import annotations
from collections import Counter


class Recommender:
    def __init__(self, config):
        self.config = config

    def recommend(self, history: list[dict], catalog: list[dict],
                  season_tag: str | None = None, limit: int = 10) -> list[dict]:
        if not history:
            popular = sorted(catalog, key=lambda c: c.get("popularity", 0), reverse=True)
            return popular[:limit]
        county_w = Counter(h["county_id"] for h in history if h.get("county_id"))
        sector_w = Counter(h["sector_id"] for h in history if h.get("sector_id"))
        bought = {h["item_id"] for h in history}
        scored = []
        for item in catalog:
            if item["id"] in bought:
                continue
            s = 2.0 * county_w.get(item.get("county_id"), 0) \
                + 1.5 * sector_w.get(item.get("sector_id"), 0)
            if season_tag and item.get("season_tag") == season_tag:
                s += 0.5
            scored.append({**item, "relevance": round(s, 3)})
        return sorted(scored, key=lambda x: x["relevance"], reverse=True)[:limit]
