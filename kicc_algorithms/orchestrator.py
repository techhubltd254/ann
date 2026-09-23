"""KiccPlatform - the orchestrator that wires all 20 algorithms into ONE system.

Event chain (the "fully connected" part):
  M-Pesa callback -> settle (idempotent) -> escrow funded
    -> escrow released -> commission log + settlement batch
    -> pool contribution -> monthly distribution (contribution x quality)
    -> sponsor referral -> county attribution
Governance chain: KYB tier -> screening gate -> participation
Data chain: weather ingest -> seasonal calendar -> itinerary + pricing
"""
from __future__ import annotations
from decimal import Decimal

from .core.config import Config
from .core.db import InMemoryRepository
from .core.cache import TTLCache
from .core.ledger import Ledger
from .core.events import EventBus
from .core.idempotency import IdempotencyStore
from .core.ratelimit import TokenBucket

from .algorithms.display import StatsAggregator, FeaturedSelector
from .algorithms.trust import TrustScorer
from .algorithms.vendors import VendorScorer
from .algorithms.pricing import DynamicPricer
from .algorithms.analytics import Analytics
from .algorithms.billing import Billing
from .algorithms.recommendations import Recommender
from .algorithms.correlation import Correlation
from .algorithms.payments import MpesaSettlement
from .algorithms.quality import QualityScorer
from .algorithms.pool import PoolEngine
from .algorithms.kyb import KybRegistry
from .algorithms.sponsorship import SponsorshipGraph
from .algorithms.county import CountyClassifier
from .algorithms.screening import Screener
from .algorithms.itinerary import ItineraryComposer
from .algorithms.weather import WeatherIngestor
from .algorithms.anonymizer import KAnonymizer


class KiccPlatform:
    def __init__(self, config_overrides: dict | None = None):
        self.config = Config(config_overrides)
        self.repo = InMemoryRepository()
        self.cache = TTLCache(default_ttl=self.config.get("display.stats_ttl_seconds"))
        self.ledger = Ledger()
        self.events = EventBus()
        self.idem = IdempotencyStore()
        self.limiter = TokenBucket(
            rate_per_sec=self.config.get("ratelimit.rate_per_sec"),
            capacity=self.config.get("ratelimit.capacity"),
        )

        self.stats = StatsAggregator(self.repo, self.cache, self.config)
        self.featured = FeaturedSelector(self.repo, self.cache, self.config)
        self.trust = TrustScorer(self.repo)
        self.vendors = VendorScorer(self.config)
        self.pricing = DynamicPricer(self.config)
        self.analytics = Analytics()
        self.billing = Billing(self.config)
        self.recommender = Recommender(self.config)
        self.correlation = Correlation()
        self.payments = MpesaSettlement(self.repo, self.ledger, self.config, self.events, self.idem)
        self.quality = QualityScorer(self.config)
        self.pool = PoolEngine(self.config)
        self.kyb = KybRegistry()
        self.sponsorship = SponsorshipGraph(self.config)
        self.counties = CountyClassifier()
        self.screener = Screener(self.config)
        self.itinerary = None          # set wire_itinerary() with live ports
        self.weather = None
        self.anonymizer = KAnonymizer(self.config)

        # ---- THE WIRING: escrow release -> commission -> pool ----
        self.events.subscribe("escrow.released", self._accrue_commission)
        self.events.subscribe("escrow.released", self._accrue_pool_contribution)
        self.pending_contributions: list[dict] = []

    # ---------- money path ----------
    async def pay(self, **kwargs) -> dict:
        """M-Pesa callback -> idempotent settlement -> escrow funded."""
        return await self.payments.settle_callback(**kwargs)

    async def release_escrow(self, *, escrow_id: str, order_id: int, seller_id: int,
                             buyer_id: int, gross, county_id: int, sector_id: int,
                             sponsor_id: int | None = None) -> dict:
        """Delivery confirmed -> split release -> commission + pool accrual."""
        escrows = await self.repo.find("escrow_transactions", escrow_id=escrow_id)
        esc = escrows[0] if escrows else await self.repo.insert("escrow_transactions", {
            "escrow_id": escrow_id, "order_id": order_id, "seller_id": seller_id,
            "buyer_id": buyer_id, "status": "funded",
            "gross": Decimal(str(gross)),
        })
        fee = (Decimal(str(gross)) * Decimal(str(self.config.get("escrow.fee_rate")))).quantize(Decimal("0.01"))
        seller_net = Decimal(str(gross)) - fee
        # settle the buyer's held funds: seller net + platform fee, one atomic move
        self.ledger.release_hold_split(
            f"wallet:buyer:{buyer_id}",
            destinations=[(f"seller:{seller_id}", seller_net), ("platform:fees", fee)],
            ref=f"escrow:{escrow_id}",
            idem_key=f"release:{escrow_id}",
        )
        esc["status"] = "released"
        await self.events.publish("escrow.released", {
            "escrow_id": escrow_id, "order_id": order_id, "seller_id": seller_id,
            "buyer_id": buyer_id, "gross": Decimal(str(gross)), "fee": fee,
            "county_id": county_id, "sector_id": sector_id, "sponsor_id": sponsor_id,
        })
        return {"escrow_id": escrow_id, "seller_net": seller_net, "platform_fee": fee}

    async def _accrue_commission(self, evt: dict):
        await self.repo.insert("commission_logs", {
            "escrow_id": evt["escrow_id"], "seller_id": evt["seller_id"],
            "amount": evt["fee"], "rate": self.config.get("escrow.fee_rate"),
        })

    async def _accrue_pool_contribution(self, evt: dict):
        self.pending_contributions.append({
            "entity_id": evt["seller_id"], "pool_share": evt["fee"],
            "county_id": evt["county_id"], "sector_id": evt["sector_id"],
        })
        if evt.get("sponsor_id") is not None:
            await self.repo.insert("sponsor_referrals", {
                "sponsor_id": evt["sponsor_id"], "seller_id": evt["seller_id"],
                "amount": self.sponsorship.referral_fee(2, evt["fee"]),
            })

    # ---------- monthly close ----------
    def run_pool_distribution(self, period: str) -> list[dict]:
        rows = self.pool.distribute(self.pending_contributions, {})
        for r in rows:
            r["period_id"] = period
        self.pending_contributions = []
        return rows

    # ---------- onboarding path ----------
    def onboard(self, **kw) -> dict:
        kw = dict(kw)                       # local copy; never mutate caller's dict
        legal_name = kw.pop("legal_name", "")
        tier = self.kyb.evaluate(**kw)
        if tier["approved"] and tier["tier"] >= 1:
            screening = self.screener.screen(legal_name)
            if screening["hit"]:
                return {**tier, "screening": screening, "participation": "frozen"}
        return tier

    # ---------- read path (million-user hot path) ----------
    async def homepage(self) -> dict:
        stats = await self.stats.get()
        featured = await self.featured.get()
        return {"stats": stats, "featured_exhibitions": featured}

    def quality_of(self, *, delivery_rate, adverse_rate, trust_grade,
                   completeness, avg_review, media_tier) -> dict:
        return self.quality.score(delivery_rate=delivery_rate, adverse_rate=adverse_rate,
                                  trust_grade=trust_grade, completeness=completeness,
                                  avg_review=avg_review, media_tier=media_tier)

    def classify_counties(self, counties: list[dict]) -> list[dict]:
        return [self.counties.classify(c) for c in counties]

    def anonymize_mcp(self, rows: list[dict]) -> list[dict]:
        return self.anonymizer.anonymize(rows)

    def wire_itinerary(self, flights, hotels, attractions, weather):
        self.itinerary = ItineraryComposer(flights, hotels, attractions, weather)
        return self.itinerary

    def wire_weather(self, feed):
        self.weather = WeatherIngestor(self.repo, feed, self.cache)
        return self.weather

    def audit(self) -> dict:
        return {"ledger_sum": str(self.ledger.audit_sum()),
                "journal_entries": len(self.ledger.journal),
                "cache_hits": self.cache.hits, "cache_misses": self.cache.misses}
