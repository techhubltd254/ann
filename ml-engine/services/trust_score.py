"""Trust Score engine — mirrors and extends the Laravel ScoreVendors formula."""
from typing import Optional

class TrustScoreEngine:
    # Weights (matching Laravel ScoreVendors)
    WEIGHTS = {
        "verification": 40,
        "fulfillment": 30,
        "breadth": 10,
        "age": 10,
        "dispute_penalty": 20,
    }

    @classmethod
    def compute(
        cls,
        email_verified: bool = False,
        phone_verified: bool = False,
        kra_pin: Optional[str] = None,
        id_number: Optional[str] = None,
        total_orders: int = 0,
        fulfilled_orders: int = 0,
        product_count: int = 0,
        account_age_days: int = 0,
        dispute_count: int = 0,
    ) -> int:
        verification = 0
        if email_verified: verification += 10
        if phone_verified: verification += 10
        if kra_pin: verification += 15
        if id_number: verification += 5

        fulfillment_score = int(round(30 * fulfilled_orders / max(total_orders, 1))) if total_orders > 0 else 15
        breadth = min(10, product_count)
        age = min(10, account_age_days // 30)
        dispute_penalty = min(20, dispute_count * 5)

        trust = max(0, min(100, verification + fulfillment_score + breadth + age - dispute_penalty))
        return trust

    @classmethod
    def to_grade(cls, score: int) -> str:
        if score >= 80: return "A"
        if score >= 60: return "B"
        if score >= 40: return "C"
        return "D"