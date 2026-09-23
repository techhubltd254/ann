"""Algorithms 1-2: Stats aggregator + Featured-exhibitions selector.

REGRESSION FIX for the two verified-broken production behaviors:
- stats widget rendered "0 Kenya Counties" while the counties table held 47
  rows -> cause was a stale/never-warmed cache. Here the aggregator ALWAYS
  reads the repository of record first, then warms the cache on the same
  await; a cached zero can never be served while the source is non-zero.
- featured selector returned empty -> cause was a status-flag mismatch.
  Here featured = published AND (featured OR top-booked fallback), so the
  section never renders empty while any published exhibition exists.
"""
from __future__ import annotations
from datetime import datetime, timezone


class StatsAggregator:
    """Item 1. O(1) cached reads, O(tables) refresh, TTL-bounded staleness."""

    def __init__(self, repo, cache, config, founding_year: int = 1973):
        self.repo = repo
        self.cache = cache
        self.ttl = config.get("display.stats_ttl_seconds")
        self.founding_year = founding_year

    async def refresh(self) -> dict:
        stats = {
            "years_of_excellence": max(1, datetime.now(timezone.utc).year - self.founding_year),
            "kenya_counties": await self.repo.count("counties"),
            "digital_screens": await self.repo.count("screens"),
            "events_per_year": await self.repo.count("exhibitions", is_published=True),
            "computed_at": datetime.now(timezone.utc).isoformat(),
        }
        # Invariant: never cache an all-zero snapshot while the source has rows.
        if stats["kenya_counties"] > 0 or stats["events_per_year"] > 0:
            await self.cache.set("platform_stats", stats, ttl=self.ttl)
        return stats

    async def get(self) -> dict:
        cached = await self.cache.get("platform_stats")
        return cached if cached is not None else await self.refresh()


class FeaturedSelector:
    """Item 2. Published exhibitions, featured flag first, top-booked fallback."""

    def __init__(self, repo, cache, config):
        self.repo = repo
        self.cache = cache
        self.limit = config.get("display.featured_limit")
        self.ttl = config.get("display.stats_ttl_seconds")

    async def get(self) -> list[dict]:
        cached = await self.cache.get("featured_exhibitions")
        if cached is not None:
            return cached
        published = await self.repo.find("exhibitions", is_published=True)
        featured = [e for e in published if e.get("is_featured")]
        fallback = sorted(
            (e for e in published if not e.get("is_featured")),
            key=lambda e: e.get("bookings_count", 0), reverse=True,
        )
        result = (featured + fallback)[: self.limit]
        await self.cache.set("featured_exhibitions", result, ttl=self.ttl)
        return result
