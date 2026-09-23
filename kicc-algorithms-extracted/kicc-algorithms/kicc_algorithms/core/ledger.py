"""Double-entry ledger with hold/release - the Core primitive set from v4 1.2.

Balances are signed Decimals. Every posting debits one account and credits
another by identical amounts, so the sum of all balances PLUS all held funds
is always zero - the audit invariant that makes the Mother Pool defensible
in a CBK licensing conversation.
"""
import itertools
import time
from collections import defaultdict
from decimal import Decimal


class PostingError(Exception):
    pass


class Ledger:
    def __init__(self):
        self.balances: dict[str, Decimal] = defaultdict(Decimal)
        self.held: dict[str, Decimal] = defaultdict(Decimal)
        self.journal: list[dict] = []
        self._idem: set[str] = set()
        self._seq = itertools.count(1)

    def _entry(self, **kw) -> dict:
        kw["seq"] = next(self._seq)
        kw["ts"] = time.time()
        self.journal.append(kw)
        return kw

    def post(self, debit: str, credit: str, amount, ref: str = "", idem_key: str | None = None):
        amount = Decimal(str(amount))
        if amount <= 0:
            raise PostingError("amount must be positive")
        if idem_key:
            if idem_key in self._idem:
                return None
            self._idem.add(idem_key)
        self.balances[debit] += amount
        self.balances[credit] -= amount
        return self._entry(type="post", debit=debit, credit=credit, amount=amount, ref=ref)

    def hold(self, account: str, amount, idem_key: str | None = None):
        amount = Decimal(str(amount))
        if idem_key:
            if idem_key in self._idem:
                return None
            self._idem.add(idem_key)
        if self.balances[account] < amount:
            raise PostingError(f"insufficient available balance in {account}")
        self.balances[account] -= amount
        self.held[account] += amount
        return self._entry(type="hold", account=account, amount=amount)

    def release_hold_split(self, account: str, destinations: list, ref: str = "",
                           idem_key: str | None = None):
        """Settle held funds to multiple destinations in one atomic move
        (e.g. seller 95% + platform fee 5% on escrow release)."""
        destinations = [(to, Decimal(str(a))) for to, a in destinations]
        total = sum(a for _, a in destinations)
        if idem_key:
            if idem_key in self._idem:
                return None
            self._idem.add(idem_key)
        if self.held[account] < total:
            raise PostingError(f"insufficient held funds in {account}")
        self.held[account] -= total
        for to, amt in destinations:
            self.balances[to] += amt
        return self._entry(type="release", account=account, destinations=destinations, ref=ref)

    def cancel_hold(self, account: str, amount, ref: str = ""):
        amount = Decimal(str(amount))
        if self.held[account] < amount:
            raise PostingError("insufficient held funds")
        self.held[account] -= amount
        self.balances[account] += amount
        return self._entry(type="cancel_hold", account=account, amount=amount, ref=ref)

    def audit_sum(self) -> Decimal:
        """Zero when every unit of value entered through a posting."""
        return sum(self.balances.values()) + sum(self.held.values())
