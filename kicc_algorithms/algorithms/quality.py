"""Algorithm 12: Quality score - the weighted composite feeding the pool.

Built ONLY from conduct inputs (delivery, disputes, trust, completeness,
reviews, media presence). Purchased visibility can never enter - the
fairness rule made monetary. Weights come from config, versioned.
"""
from __future__ import annotations
from decimal import Decimal, ROUND_HALF_UP


class QualityScorer:
    def __init__(self, config):
        self.w = config.get("quality_weights")

    def score(self, *, delivery_rate: float, adverse_rate: float, trust_grade: str,
              completeness: float, avg_review: float, media_tier: int) -> dict:
        trust_map = {"A": 1.0, "B": 0.8, "C": 0.6, "D": 0.4, "F": 0.2}
        components = {
            "delivery": max(0.0, min(1.0, delivery_rate)),
            "disputes": max(0.0, min(1.0, 1.0 - adverse_rate)),
            "trust": trust_map.get(trust_grade, 0.2),
            "completeness": max(0.0, min(1.0, completeness)),
            "reviews": max(0.0, min(1.0, avg_review / 5.0)),
            "media": max(0.0, min(1.0, media_tier / 3.0)),
        }
        total = sum(self.w[k] * v for k, v in components.items())
        return {"score": float(Decimal(str(total)).quantize(Decimal("0.0001"), ROUND_HALF_UP)),
                "version": "quality_weights@config-1", "components": components}
