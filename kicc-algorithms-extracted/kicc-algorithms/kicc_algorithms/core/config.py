"""Central configuration - every tunable weight/threshold lives here.

Mirrors the config/kicc.php convention used by the existing 32 algorithms
(commit c4cff55). Runtime overrides merge deep over DEFAULTS, so a deployment
can tune pool alpha/beta or screening thresholds without code changes.
"""
from copy import deepcopy

DEFAULTS = {
    "platform": {"founding_year": 1973, "base_currency": "KES"},
    "display": {"stats_ttl_seconds": 60, "featured_limit": 6},
    "escrow": {"fee_rate": 0.05},
    "commission": {"default_rate": 0.03},
    "billing": {"vat_rate": 0.16},
    "pricing": {"min_multiplier": 0.9, "max_multiplier": 1.5, "sensitivity": 0.5},
    "quality_weights": {
        "delivery": 0.25, "disputes": 0.20, "trust": 0.15,
        "completeness": 0.15, "reviews": 0.15, "media": 0.10,
    },
    "pool": {
        "alpha": 0.7, "beta": 0.3, "holdback_pct": 10.0,
        "equalisation_pct": 0.5, "default_quality": 0.5,
    },
    "sponsorship": {"referral_pct": {"tier1": 0.01, "tier2": 0.02}},
    "screening": {"threshold": 0.85},
    "anonymizer": {"k": 5},
    "ratelimit": {"rate_per_sec": 1000.0, "capacity": 5000},
}


class Config:
    def __init__(self, overrides: dict | None = None):
        self._d = deepcopy(DEFAULTS)
        if overrides:
            self._merge(self._d, overrides)

    def _merge(self, base: dict, extra: dict):
        for k, v in extra.items():
            if isinstance(v, dict) and isinstance(base.get(k), dict):
                self._merge(base[k], v)
            else:
                base[k] = v

    def get(self, dotted: str):
        cur = self._d
        for part in dotted.split("."):
            cur = cur[part]
        return cur
