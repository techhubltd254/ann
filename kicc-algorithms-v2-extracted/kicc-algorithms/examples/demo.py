"""End-to-end demo: one M-Pesa payment through escrow, release, pool close."""
import asyncio
import sys
import pathlib
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1]))
from decimal import Decimal
from kicc_algorithms import KiccPlatform

async def main():
    p = KiccPlatform()
    print("== 1. M-Pesa callback (idempotent) ==")
    r1 = await p.pay(order_id=1, buyer_id=10, seller_id=20, amount="10000",
                     mpesa_ref="SLD8XK91", county_id=21, sector_id=2)
    r2 = await p.pay(order_id=1, buyer_id=10, seller_id=20, amount="10000",
                     mpesa_ref="SLD8XK91")  # duplicate webhook
    print(r1, r2)

    print("== 2. Delivery confirmed -> escrow release ==")
    out = await p.release_escrow(escrow_id="ESC-001", order_id=1, seller_id=20,
                                 buyer_id=10, gross="10000", county_id=21,
                                 sector_id=2, sponsor_id=99)
    print(out)

    print("== 3. Monthly pool close ==")
    for row in p.run_pool_distribution("2026-09"):
        print({k: (str(v) if isinstance(v, Decimal) else v) for k, v in row.items()})

    print("== 4. County classification ==")
    for c in p.classify_counties([
        {"name": "Nairobi", "gmv": 100, "water": 0.1, "roads": 0.1},
        {"name": "Turkana", "gmv": 2, "water": 0.9, "roads": 0.9},
    ]):
        print(c)

    print("== 5. Ledger audit ==")
    print(p.audit())

if __name__ == "__main__":
    asyncio.run(main())
