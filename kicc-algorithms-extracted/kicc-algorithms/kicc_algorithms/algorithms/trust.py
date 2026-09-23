"""Algorithms 3-4: Escrow trust grades + dispute-rate scoring.

Trust grade per seller from completed escrow lifecycle rows:
  grade A >= 0.98 delivery rate with >= 10 samples (statistically meaningful)
  grade B >= 0.90, grade C >= 0.75, grade D below, F for new/insufficient data
Dispute score penalises OPEN and LOST cases; WON disputes don't count against.
"""
from __future__ import annotations
from decimal import Decimal

GRADES = ((0.98, "A"), (0.90, "B"), (0.75, "C"), (0.0, "D"))


class TrustScorer:
    def __init__(self, repo):
        self.repo = repo

    async def grade(self, seller_id: int) -> dict:
        escrows = await self.repo.find("escrow_transactions", seller_id=seller_id)
        delivered = [e for e in escrows if e.get("status") == "released"]
        sample = len(escrows)
        rate = (len(delivered) / sample) if sample else 0.0
        letter = "F" if sample < 10 else next(g for t, g in GRADES if rate >= t)
        return {
            "seller_id": seller_id, "sample_size": sample,
            "delivery_rate": round(rate, 4), "grade": letter,
            "trust_component": rate,  # feeds quality_weights["trust"]
        }

    async def dispute_score(self, seller_id: int) -> dict:
        cases = await self.repo.find("dispute_cases", seller_id=seller_id)
        total = len(cases)
        adverse = sum(1 for c in cases if c.get("status") in ("open", "lost"))
        rate = (adverse / total) if total else 0.0
        # component value for quality scoring: 1.0 is perfect
        component = Decimal(1 - rate).quantize(Decimal("0.0001"))
        return {
            "seller_id": seller_id, "cases": total, "adverse": adverse,
            "adverse_rate": round(rate, 4), "dispute_component": float(component),
        }
