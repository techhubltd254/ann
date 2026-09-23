"""Per-key token-bucket rate limiter - protects the MCP/API surface."""


class TokenBucket:
    def __init__(self, rate_per_sec: float, capacity: int):
        self.rate = float(rate_per_sec)
        self.capacity = float(capacity)
        self._buckets: dict[str, tuple[float, float]] = {}

    def allow(self, key: str, tokens: float = 1.0) -> bool:
        import time
        now = time.monotonic()
        level, updated = self._buckets.get(key, (self.capacity, now))
        level = min(self.capacity, level + (now - updated) * self.rate)
        if level < tokens:
            self._buckets[key] = (level, now)
            return False
        self._buckets[key] = (level - tokens, now)
        return True
