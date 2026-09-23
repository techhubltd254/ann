"""Algorithm 11: M-Pesa settlement -> commission chain.

Simulates the STK-push callback path end-to-end with idempotency:
callback -> payment record -> settlement batch -> commission_logs row ->
pool contribution event. Duplicate callbacks are swallowed by the
idempotency key (the exact production failure mode at scale).
"""
from __future__ import annotations
from decimal import Decimal


class MpesaSettlement:
    def __init__(self, repo, ledger, config, events, idem):
        self.repo, self.ledger, self.events, self.idem = repo, ledger, events, idem
        self.commission_rate = Decimal(str(config.get("commission.default_rate")))

    async def settle_callback(self, *, order_id: int, buyer_id: int, seller_id: int,
                              amount, mpesa_ref: str, county_id: int | None = None,
                              sector_id: int | None = None) -> dict:
        idem_key = f"mpesa:{mpesa_ref}"
        if not self.idem.check_and_reserve(idem_key):
            return {"status": "duplicate_ignored", "mpesa_ref": mpesa_ref}

        amount = Decimal(str(amount))
        payment = await self.repo.insert("payments", {
            "order_id": order_id, "buyer_id": buyer_id, "seller_id": seller_id,
            "amount": amount, "mpesa_ref": mpesa_ref, "status": "received",
        })
        # funds enter the buyer wallet from the outside world, then are
        # HELD for the order (escrow semantics) until delivery confirms.
        wallet = f"wallet:buyer:{buyer_id}"
        self.ledger.post(debit=wallet, credit="outside:world", amount=amount,
                         ref=f"payment:{payment['id']}", idem_key=idem_key + ":post")
        self.ledger.hold(wallet, amount, idem_key=idem_key + ":hold")

        commission = (amount * self.commission_rate).quantize(Decimal("0.01"))
        log = await self.repo.insert("commission_logs", {
            "payment_id": payment["id"], "seller_id": seller_id,
            "amount": commission, "rate": float(self.commission_rate),
        })
        settlement = await self.repo.insert("settlement_batches", {
            "commission_log_id": log["id"], "status": "batched",
        })
        await self.events.publish("escrow.funded", {
            "order_id": order_id, "seller_id": seller_id, "buyer_id": buyer_id,
            "gross": amount, "commission": commission,
            "county_id": county_id, "sector_id": sector_id,
        })
        return {"status": "settled", "payment_id": payment["id"],
                "commission": commission, "settlement_id": settlement["id"]}
