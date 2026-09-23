"""Algorithm 5: ScoreVendors - composite vendor score for ranking.

Inputs: trust grade, dispute score, reviews, data completeness (algorithm 10),
media tier. Output 0-100. Purchased visibility NEVER enters this score
(fairness rule: ads buy placement, not score).
"""
from __future__ import annotations


class VendorScorer:
    def __init__(self, config):
        self.w = config.get("quality_weights")

    def score(self, *, delivery_rate: float, adverse_rate: float, trust_grade: str,
              avg_review: float, completeness: float, media_tier: int) -> dict:
        trust_map = {"A": 1.0, "B": 0.8, "C": 0.6, "D": 0.4, "F": 0.2}
        components = {
            "delivery": delivery_rate,
            "disputes": 1.0 - adverse_rate,
            "trust": trust_map.get(trust_grade, 0.2),
            "completeness": min(1.0, max(0.0, completeness)),
            "reviews": min(1.0, max(0.0, avg_review / 5.0)),
            "media": min(1.0, max(0.0, media_tier / 3.0)),
        }
        total = sum(self.w[k] * v for k, v in components.items())
        return {"score": round(total * 100, 2), "components": components}
