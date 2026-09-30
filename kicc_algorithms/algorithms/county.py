"""Algorithm 15: County classification - dual score, four quadrants.

RPS: revenue potential from pipeline data. FNS: foundational need
(CRA marginalization criteria). The two scores are NEVER combined into
one ranking - the quadrant IS the output (v4 governance: no single list).
"""
from __future__ import annotations


class CountyClassifier:
    RPS_FIELDS = ("gmv", "tourism_bookings", "agri_exports", "sez_pipeline", "procurement_flow")
    FNS_FIELDS = ("water", "health", "roads", "power", "security", "education")

    def rps(self, county: dict) -> float:
        vals = [county.get(f, 0) for f in self.RPS_FIELDS]
        mx = max(vals) or 1
        return round(sum(v / mx for v in vals) / len(vals), 4)

    def fns(self, county: dict) -> float:
        """0 = adequate infrastructure, 1 = severe gap (CRA-style)."""
        vals = [min(1.0, county.get(f, 0)) for f in self.FNS_FIELDS]
        return round(sum(vals) / len(vals), 4)

    def classify(self, county: dict, rps_threshold: float = 0.5,
                 fns_threshold: float = 0.5) -> dict:
        r, f = self.rps(county), self.fns(county)
        if r >= rps_threshold and f < fns_threshold:
            q = "engine"
        elif r < rps_threshold and f < fns_threshold:
            q = "growth"
        elif r >= rps_threshold and f >= fns_threshold:
            q = "priority_development"
        else:
            q = "foundational_anchor"
        return {"county": county.get("name"), "rps": r, "fns": f, "quadrant": q}
