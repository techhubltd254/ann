"""Algorithm 20: k-anonymity anonymizer for the MCP order/escrow surface.

Generalizes quasi-identifiers (county, sector) and truncates amounts into
bands until every equivalence class has >= k members - the minimum bar
before the data-product pipeline (G4) can sell anything.
"""
from __future__ import annotations
from collections import Counter


class KAnonymizer:
    def __init__(self, config):
        self.k = config.get("anonymizer.k")

    @staticmethod
    def _band(amount: float) -> str:
        bands = (100, 1_000, 10_000, 100_000, 1_000_000)
        lo = 0
        for hi in bands:
            if amount < hi:
                return f"{lo}-{hi}"
            lo = hi
        return "1M+"

    def anonymize(self, rows: list[dict], quasi: tuple[str, ...] = ("county_id", "sector_id")) -> list[dict]:
        generalized = [
            {**{q: r.get(q) for q in quasi},
             "amount_band": self._band(float(r.get("amount", 0)))}
            for r in rows
        ]
        counts = Counter(tuple(g.values()) for g in generalized)
        return [g for g in generalized if counts[tuple(g.values())] >= self.k]
