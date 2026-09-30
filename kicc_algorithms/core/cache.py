"""Async TTL cache with eviction - the hot-path shield for million-user reads.

Every read-heavy algorithm (stats, featured, screening memo) goes through
this. Swap for Redis by implementing get/set/invalidate against a pooled
client; the interface is deliberately tiny.
"""
import asyncio
import time


class TTLCache:
    def __init__(self, default_ttl: float = 60.0, max_entries: int = 100_000):
        self._store: dict = {}
        self._default_ttl = default_ttl
        self._max = max_entries
        self._lock = asyncio.Lock()
        self.hits = 0
        self.misses = 0

    async def get(self, key: str):
        item = self._store.get(key)
        if item is None:
            self.misses += 1
            return None
        expires, value = item
        if expires < time.monotonic():
            self._store.pop(key, None)
            self.misses += 1
            return None
        self.hits += 1
        return value

    async def set(self, key: str, value, ttl: float | None = None):
        async with self._lock:
            if len(self._store) >= self._max:
                oldest = min(self._store, key=lambda k: self._store[k][0])
                self._store.pop(oldest, None)
        self._store[key] = (time.monotonic() + (ttl or self._default_ttl), value)

    async def invalidate(self, key: str):
        self._store.pop(key, None)
