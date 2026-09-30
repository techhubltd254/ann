"""Idempotency guard - every money-moving operation reserves its key exactly once.

At million-user scale, duplicate webhooks and client retries are the norm,
not the exception. Backed by an in-memory set here; migrations/001_core.sql
creates the idempotency_keys table with a unique index for the durable
adapter - check_and_reserve() maps to INSERT ... ON CONFLICT DO NOTHING.
"""
import threading


class IdempotencyStore:
    def __init__(self):
        self._seen: set[str] = set()
        self._lock = threading.Lock()

    def check_and_reserve(self, key: str) -> bool:
        """True if this is the first time `key` is seen; False on replay."""
        with self._lock:
            if key in self._seen:
                return False
            self._seen.add(key)
            return True
