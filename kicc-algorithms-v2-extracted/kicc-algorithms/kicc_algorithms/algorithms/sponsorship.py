"""Algorithm 14: Sponsorship graph + referral engine.

Sponsors pay onboarding costs and earn a referral bps on sponsored-business
transactions. Zero approval authority - the evaluate() in kyb.py is the
ONLY admission path. Graph edges are explicit and auditable.
"""
from __future__ import annotations
from collections import defaultdict
from decimal import Decimal


class SponsorshipGraph:
    def __init__(self, config):
        self.pct = {k: Decimal(str(v)) for k, v in
                    config.get("sponsorship.referral_pct").items()}

    def register(self, sponsor_id: int, sponsor_tier: int, sponsored_id: int) -> dict:
        if sponsor_tier < 1:
            raise ValueError("sponsor must be Tier 1+")
        return {"edge": f"{sponsor_id}->sponsors->{sponsored_id}",
                "referral_pct": float(self.pct[f"tier{min(2, sponsor_tier)}"]),
                "approval_authority": False}

    def referral_fee(self, sponsor_tier: int, transaction_amount) -> Decimal:
        key = f"tier{min(2, max(1, sponsor_tier))}"
        return (Decimal(str(transaction_amount)) * self.pct[key]).quantize(Decimal("0.01"))

    def downstream(self, sponsor_id: int, edges: list[tuple[int, int]]) -> list[int]:
        children = defaultdict(list)
        for s, b in edges:
            children[s].append(b)
        seen, stack = [], [sponsor_id]
        while stack:
            node = stack.pop()
            for child in children.get(node, []):
                seen.append(child)
                stack.append(child)
        return seen
