"""Algorithm 13: KYB tier registry (T0 phone / T1 KRA-PIN / T2 verified-asset).

Pure decision function: objective checks only, no human veto path exists
in the interface by construction (v4 1.5 governance rule).
"""
from __future__ import annotations
import re

KRA_PIN = re.compile(r"^[A-Z][0-9]{9}[A-Z]$|^[A-Z]{2}[0-9]{7}[A-Z]$")


class KybRegistry:
    def evaluate(self, *, phone_verified: bool, kra_pin: str | None = None,
                 pin_validated: bool = False, asset_verified: bool = False) -> dict:
        if not phone_verified:
            return {"tier": 0, "approved": True, "reason": "phone-verified only"}
        if asset_verified and pin_validated and kra_pin and KRA_PIN.match(kra_pin):
            return {"tier": 2, "approved": True, "reason": "asset + validated PIN"}
        if pin_validated and kra_pin and KRA_PIN.match(kra_pin):
            return {"tier": 1, "approved": True, "reason": "validated KRA PIN"}
        if kra_pin and not KRA_PIN.match(kra_pin):
            return {"tier": 0, "approved": False, "reason": "KRA PIN format invalid"}
        return {"tier": 0, "approved": False, "reason": "PIN validation pending"}
