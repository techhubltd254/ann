"""Algorithm 16: Mother Pool distribution engine (contribution x quality).

All proceeds from every sector/county/pipeline accrue into one pool; monthly
payouts flow back weighted by contribution^alpha * quality^beta. Held-back
funds (disputes/refunds) and the 0.5% equalisation earmark (Art. 204(1)
precedent) are reserved BEFORE distribution. Every payout row carries its
full weight trace - the explainability requirement, implemented.
"""
from __future__ import annotations
from decimal import Decimal, ROUND_HALF_UP


class PoolEngine:
    def __init__(self, config):
        p = config.get("pool")
        self.alpha = Decimal(str(p["alpha"]))
        self.beta = Decimal(str(p["beta"]))
        self.holdback = Decimal(str(p["holdback_pct"])) / 100
        self.equalisation = Decimal(str(p["equalisation_pct"])) / 100
        self.default_q = Decimal(str(p["default_quality"]))

    def distribute(self, contributions: list[dict], qualities: dict[int, float]) -> list[dict]:
        """contributions: [{entity_id, pool_share}] ; qualities: {entity_id: 0..1}."""
        if not contributions:
            return []
        inflow = sum(Decimal(str(c["pool_share"])) for c in contributions)
        reserves = inflow * (self.holdback + self.equalisation)
        distributable = inflow - reserves

        by_entity: dict[int, Decimal] = {}
        for c in contributions:
            by_entity[c["entity_id"]] = by_entity.get(c["entity_id"], Decimal(0)) \
                + Decimal(str(c["pool_share"]))
        total = sum(by_entity.values())

        weights: dict[int, Decimal] = {}
        for entity_id, share in by_entity.items():
            contrib = share / total
            q = Decimal(str(qualities.get(entity_id, float(self.default_q))))
            weights[entity_id] = (contrib ** self.alpha) * (q ** self.beta)
        wsum = sum(weights.values()) or Decimal(1)

        rows = []
        for entity_id, w in weights.items():
            amount = (distributable * w / wsum).quantize(Decimal("0.01"), ROUND_HALF_UP)
            rows.append({
                "entity_id": entity_id,
                "contribution_weight": float((by_entity[entity_id] / total).quantize(Decimal("0.000001"))),
                "quality_weight": float(qualities.get(entity_id, float(self.default_q))),
                "final_weight": float(w.quantize(Decimal("0.000001"))),
                "amount": amount,
                "breakdown": {
                    "inflow": float(inflow), "reserves": float(reserves),
                    "distributable": float(distributable),
                    "alpha": float(self.alpha), "beta": float(self.beta),
                },
            })
        # cent-perfect: last row absorbs rounding remainder
        drift = distributable - sum(r["amount"] for r in rows)
        if drift:
            rows[-1]["amount"] += drift
        return rows
