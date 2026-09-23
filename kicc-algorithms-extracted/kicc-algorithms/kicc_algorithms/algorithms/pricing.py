"""Algorithm 6: ExperiencePricingService - demand-responsive dynamic pricing.

Multiplier bounded by config (default 0.9x-1.5x) so prices never go
underwater or gouge; clamped, deterministic, explainable.
"""
from __future__ import annotations


class DynamicPricer:
    def __init__(self, config):
        self.min_m = config.get("pricing.min_multiplier")
        self.max_m = config.get("pricing.max_multiplier")
        self.sens = config.get("pricing.sensitivity")

    def multiplier(self, *, occupancy: float, days_to_event: int, season_tag: str) -> float:
        occ = min(1.0, max(0.0, occupancy))
        urgency = 0.15 if days_to_event <= 7 else (0.05 if days_to_event <= 30 else 0.0)
        season = {"peak": 0.15, "high": 0.08, "shoulder": 0.0, "low": -0.05}.get(season_tag, 0.0)
        raw = 1.0 + self.sens * occ + urgency + season
        m = min(self.max_m, max(self.min_m, raw))
        return round(m, 4)

    def price(self, base_price: float, **signals) -> dict:
        m = self.multiplier(**signals)
        return {"base_price": base_price, "multiplier": m,
                "final_price": round(base_price * m, 2), "signals": signals}
