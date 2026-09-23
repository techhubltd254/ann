"""Algorithm 10: CorrelationService - data completeness + sector affinity.

Completeness: weighted fill-rate of profile fields per entity (feeds the
quality score). Affinity: co-occurrence of sector pairs within counties -
the substrate for cross-sell and the SEZ site-matching shortlist.
"""
from __future__ import annotations
from collections import defaultdict
from itertools import combinations


FIELD_WEIGHTS = {"description": 0.2, "media": 0.3, "pricing": 0.2, "contact": 0.15, "location": 0.15}


class Correlation:
    def completeness(self, entity: dict) -> float:
        flags = {
            "description": bool(entity.get("description")),
            "media": bool(entity.get("image_url") or entity.get("video_url")),
            "pricing": entity.get("price") is not None,
            "contact": bool(entity.get("phone") or entity.get("email")),
            "location": entity.get("latitude") is not None,
        }
        return round(sum(FIELD_WEIGHTS[k] for k, v in flags.items() if v), 4)

    def affinity(self, counties: list[dict]) -> dict:
        pairs: dict[tuple, int] = defaultdict(int)
        for c in counties:
            secs = sorted(set(c.get("sectors", [])))
            for a, b in combinations(secs, 2):
                pairs[(a, b)] += 1
        total = max(1, len(counties))
        return {f"{a}+{b}": round(n / total, 4) for (a, b), n in
                sorted(pairs.items(), key=lambda kv: -kv[1])[:25]}
