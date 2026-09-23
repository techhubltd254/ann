"""Algorithm 8: RunBilling - VAT-inclusive invoicing (Kenya standard 16%).

Money in Decimal; rounding is half-up on the final line only, so the sum
of parts always equals the invoice total (no cent leakage at volume).
"""
from decimal import Decimal, ROUND_HALF_UP


class Billing:
    def __init__(self, config):
        self.vat = Decimal(str(config.get("billing.vat_rate")))

    def invoice(self, line_items: list[dict]) -> dict:
        subtotal = sum((Decimal(str(i["amount"])) for i in line_items), Decimal(0))
        vat = (subtotal * self.vat).quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)
        return {
            "lines": line_items,
            "subtotal": subtotal,
            "vat": vat,
            "total": subtotal + vat,
            "currency": "KES",
        }
