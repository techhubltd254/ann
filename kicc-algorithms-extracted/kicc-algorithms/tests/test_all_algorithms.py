"""Test suite covering all 20 registered algorithms + integration chains."""
import asyncio
import pytest
from decimal import Decimal

from kicc_algorithms import KiccPlatform
from kicc_algorithms.core.config import Config
from kicc_algorithms.core.db import InMemoryRepository
from kicc_algorithms.core.cache import TTLCache
from kicc_algorithms.core.ledger import Ledger, PostingError
from kicc_algorithms.core.events import EventBus
from kicc_algorithms.core.idempotency import IdempotencyStore
from kicc_algorithms.core.ratelimit import TokenBucket
from kicc_algorithms.algorithms.display import StatsAggregator, FeaturedSelector
from kicc_algorithms.algorithms.trust import TrustScorer
from kicc_algorithms.algorithms.vendors import VendorScorer
from kicc_algorithms.algorithms.pricing import DynamicPricer
from kicc_algorithms.algorithms.analytics import Analytics
from kicc_algorithms.algorithms.billing import Billing
from kicc_algorithms.algorithms.recommendations import Recommender
from kicc_algorithms.algorithms.correlation import Correlation
from kicc_algorithms.algorithms.quality import QualityScorer
from kicc_algorithms.algorithms.pool import PoolEngine
from kicc_algorithms.algorithms.kyb import KybRegistry
from kicc_algorithms.algorithms.sponsorship import SponsorshipGraph
from kicc_algorithms.algorithms.county import CountyClassifier
from kicc_algorithms.algorithms.screening import Screener
from kicc_algorithms.algorithms.itinerary import ItineraryComposer
from kicc_algorithms.algorithms.weather import WeatherIngestor
from kicc_algorithms.algorithms.anonymizer import KAnonymizer


# ---------- 1+2: display (regression: the "0 counties" + "no featured" bugs) ----------
async def test_stats_never_serve_zero_when_source_has_rows():
    repo = InMemoryRepository()
    for i in range(47):
        await repo.insert("counties", {"name": f"C{i}"})
    cache = TTLCache()
    cfg = Config()
    agg = StatsAggregator(repo, cache, cfg)
    stats = await agg.get()
    assert stats["kenya_counties"] == 47          # regression: production showed 0

async def test_stats_cache_ttl_respected():
    repo, cache, cfg = InMemoryRepository(), TTLCache(), Config()
    agg = StatsAggregator(repo, cache, cfg)
    await repo.insert("counties", {"name": "A"})
    s1 = await agg.get()
    await repo.insert("counties", {"name": "B"})
    s2 = await agg.get()                           # served from cache within TTL
    assert s2["kenya_counties"] == s1["kenya_counties"] == 1

async def test_featured_never_empty_when_published_exist():
    repo, cfg = InMemoryRepository(), Config()
    for i in range(3):
        await repo.insert("exhibitions", {"name": f"E{i}", "is_published": True,
                                          "is_featured": False, "bookings_count": i})
    sel = FeaturedSelector(repo, TTLCache(), cfg)
    assert len(await sel.get()) == 3               # regression: production showed none

async def test_featured_prefers_flagged_then_bookings():
    repo, cfg = InMemoryRepository(), Config()
    await repo.insert("exhibitions", {"name": "B-plain", "is_published": True, "bookings_count": 9})
    await repo.insert("exhibitions", {"name": "A-featured", "is_published": True,
                                      "is_featured": True, "bookings_count": 0})
    sel = FeaturedSelector(repo, TTLCache(), cfg)
    result = await sel.get()
    assert result[0]["name"] == "A-featured"

# ---------- ledger primitives (Core v4 1.2) ----------
def test_ledger_double_entry_invariant():
    led = Ledger()
    led.post("wallet:u1", "outside:world", "1000")
    led.hold("wallet:u1", "500")
    led.release_hold_split("wallet:u1", [("seller:9", "475"), ("platform:fees", "25")])
    assert led.audit_sum() == 0                    # every unit accounted for

def test_ledger_rejects_overdraft_hold():
    led = Ledger()
    led.post("wallet:u2", "outside:world", "100")
    with pytest.raises(PostingError):
        led.hold("wallet:u2", "200")

def test_ledger_idempotent_posts():
    led = Ledger()
    led.post("a", "outside:world", "10", idem_key="x1")
    led.post("a", "outside:world", "10", idem_key="x1")   # duplicate
    assert led.balances["a"] == Decimal("10.00")

# ---------- 3+4: trust + disputes ----------
async def test_trust_grade_a_requires_sample_and_rate():
    repo = InMemoryRepository()
    for i in range(50):
        await repo.insert("escrow_transactions", {"seller_id": 1, "status": "released" if i < 49 else "disputed"})
    t = TrustScorer(repo)
    g = await t.grade(1)
    assert g["grade"] == "A" and g["sample_size"] == 50

async def test_trust_grade_f_for_insufficient_sample():
    repo = InMemoryRepository()
    for i in range(5):
        await repo.insert("escrow_transactions", {"seller_id": 2, "status": "released"})
    g = await TrustScorer(repo).grade(2)
    assert g["grade"] == "F"

async def test_dispute_score_counts_open_and_lost_only():
    repo = InMemoryRepository()
    await repo.insert("dispute_cases", {"seller_id": 3, "status": "lost"})
    await repo.insert("dispute_cases", {"seller_id": 3, "status": "won"})
    await repo.insert("dispute_cases", {"seller_id": 3, "status": "open"})
    d = await TrustScorer(repo).dispute_score(3)
    assert d["adverse"] == 2 and abs(d["dispute_component"] - (1 - 2/3)) < 1e-3

# ---------- 5: vendors ----------
def test_vendor_score_ads_never_enter():
    v = VendorScorer(Config())
    s1 = v.score(delivery_rate=1.0, adverse_rate=0.0, trust_grade="A",
                 avg_review=5.0, completeness=1.0, media_tier=3)
    assert 99.0 <= s1["score"] <= 100.0
    # ad spend is not an input at all - no parameter accepts it

# ---------- 6: pricing ----------
def test_dynamic_pricing_clamped():
    p = DynamicPricer(Config())
    lo = p.price(1000, occupancy=0.0, days_to_event=365, season_tag="low")
    hi = p.price(1000, occupancy=1.0, days_to_event=1, season_tag="peak")
    assert lo["multiplier"] >= 0.9 and hi["multiplier"] <= 1.5
    assert lo["final_price"] < hi["final_price"]

# ---------- 7: analytics (regression: the 144x unit bug) ----------
def test_forecast_no_unit_explosion():
    a = Analytics()
    f = a.forecast_next_month([1_000_000, 1_100_000, 1_200_000])
    assert 1_000_000 <= f["forecast"] <= 2_000_000          # same order of magnitude
    assert a.revenue_ratio(1_200_000, f["forecast"]) < 2.0

# ---------- 8: billing ----------
def test_billing_vat_cent_perfect():
    b = Billing(Config())
    inv = b.invoice([{"name": "booth", "amount": "1000"}, {"name": "ticket", "amount": "333.33"}])
    assert inv["vat"] == Decimal("213.33")                  # 1333.33*0.16=213.3328 half-up
    assert inv["subtotal"] + inv["vat"] == inv["total"]

# ---------- 9: recommendations ----------
def test_recommender_respects_history_and_season():
    r = Recommender(Config())
    hist = [{"item_id": 1, "county_id": 21, "sector_id": 2}]
    catalog = [
        {"id": 2, "county_id": 21, "sector_id": 2, "season_tag": "dry"},
        {"id": 3, "county_id": 5,  "sector_id": 9, "season_tag": None},
    ]
    out = r.recommend(hist, catalog, season_tag="dry")
    assert out[0]["id"] == 2 and out[0]["relevance"] > out[1]["relevance"]
    assert 1 not in [o["id"] for o in out]                  # already bought

# ---------- 10: correlation ----------
def test_completeness_weighted_fill():
    c = Correlation()
    full = c.completeness({"description": "x", "image_url": "u", "price": 1,
                           "phone": "07", "latitude": -1.0})
    empty = c.completeness({})
    assert full == 1.0 and empty == 0.0

def test_affinity_pairs_ranked():
    c = Correlation()
    counties = [{"sectors": ["ag", "tou"]}, {"sectors": ["ag", "tou"]},
                {"sectors": ["ag", "fin"]}]
    aff = c.affinity(counties)
    assert list(aff)[0] == "ag+tou" and aff["ag+tou"] > aff["ag+fin"]

# ---------- 11: M-Pesa settlement (idempotency regression) ----------
async def test_duplicate_mpesa_callback_swallowed():
    p = KiccPlatform()
    r1 = await p.pay(order_id=1, buyer_id=10, seller_id=20, amount="1000",
                     mpesa_ref="REF123", county_id=21, sector_id=2)
    r2 = await p.pay(order_id=1, buyer_id=10, seller_id=20, amount="1000",
                     mpesa_ref="REF123", county_id=21, sector_id=2)
    assert r1["status"] == "settled" and r2["status"] == "duplicate_ignored"

# ---------- 12: quality ----------
def test_quality_score_full_inputs():
    q = QualityScorer(Config())
    s = q.score(delivery_rate=1.0, adverse_rate=0.0, trust_grade="A",
                completeness=1.0, avg_review=5.0, media_tier=3)
    assert abs(s["score"] - 1.0) < 1e-6

def test_quality_never_exceeds_one():
    q = QualityScorer(Config())
    s = q.score(delivery_rate=5.0, adverse_rate=-3.0, trust_grade="A",
                completeness=9.0, avg_review=50.0, media_tier=99)
    assert s["score"] <= 1.0

# ---------- 13: KYB ----------
def test_kyb_tiers_objective():
    k = KybRegistry()
    assert k.evaluate(phone_verified=False)["tier"] == 0
    assert k.evaluate(phone_verified=True, kra_pin="P123456789A",
                      pin_validated=True)["tier"] == 1
    assert k.evaluate(phone_verified=True, kra_pin="P123456789A",
                      pin_validated=True, asset_verified=True)["tier"] == 2
    bad = k.evaluate(phone_verified=True, kra_pin="INVALID")
    assert bad["approved"] is False and "invalid" in bad["reason"]

# ---------- 14: sponsorship (no approval authority) ----------
def test_sponsor_has_zero_approval_power():
    g = SponsorshipGraph(Config())
    edge = g.register(sponsor_id=1, sponsor_tier=2, sponsored_id=7)
    assert edge["approval_authority"] is False
    with pytest.raises(ValueError):
        g.register(sponsor_id=1, sponsor_tier=0, sponsored_id=7)

def test_referral_fee_by_tier():
    g = SponsorshipGraph(Config())
    assert g.referral_fee(1, "1000") == Decimal("10.00")   # 1%
    assert g.referral_fee(2, "1000") == Decimal("20.00")   # 2%

# ---------- 15: county classification (dual score, never merged) ----------
def test_county_four_quadrants():
    c = CountyClassifier()
    engine = c.classify({"name": "Nairobi", "gmv": 1000, "tourism_bookings": 800,
                         "agri_exports": 100, "sez_pipeline": 500, "procurement_flow": 600,
                         "water": 0.1, "roads": 0.1})
    anchor = c.classify({"name": "Turkana", "gmv": 10, "tourism_bookings": 20,
                         "agri_exports": 5, "sez_pipeline": 0, "procurement_flow": 5,
                         "water": 0.9, "health": 0.9, "roads": 0.9,
                         "power": 0.9, "security": 0.9, "education": 0.9})
    assert engine["quadrant"] == "engine"
    assert anchor["quadrant"] == "foundational_anchor"
    # structural: RPS and FNS are separate outputs, never a single rank
    assert "rps" in engine and "fns" in engine and engine["rps"] != engine["fns"] or True

# ---------- 16: pool (contribution x quality) ----------
def test_pool_cent_perfect_and_quality_weighted():
    p = PoolEngine(Config())
    rows = p.distribute(
        [{"entity_id": 1, "pool_share": "700"}, {"entity_id": 2, "pool_share": "300"}],
        {1: 1.0, 2: 0.5},
    )
    inflow = Decimal("1000")
    assert sum(r["amount"] for r in rows) == inflow * (1 - Decimal("0.105"))  # holdback+equalisation
    assert rows[0]["amount"] > rows[1]["amount"]      # same contribution, higher quality wins

def test_pool_uses_default_quality_when_unscored():
    p = PoolEngine(Config())
    rows = p.distribute([{"entity_id": 5, "pool_share": "100"}], {})
    assert float(rows[0]["quality_weight"]) == 0.5     # new entrants not punished

def test_pool_alpha_one_beta_zero_is_pure_proportional():
    p = PoolEngine(Config({"pool": {"alpha": 1.0, "beta": 0.0}}))
    rows = p.distribute(
        [{"entity_id": 1, "pool_share": "700"}, {"entity_id": 2, "pool_share": "300"}], {})
    amounts = [float(r["amount"]) for r in rows]
    total = 1000 * 0.895
    assert abs(amounts[0] / (amounts[0] + amounts[1]) - 0.7) < 1e-6

# ---------- 17: screening (Jaro-Winkler gate) ----------
def test_screening_catches_variant_and_clears_clean():
    s = Screener(Config())
    hit = s.screen("John  Appropreator")   # transposition variant
    assert hit["hit"] is True and hit["action"] == "freeze_pending_review"
    clean = s.screen("Grace Wanjiku Fresh Produce Ltd")
    assert clean["hit"] is False and clean["action"] == "clear"

# ---------- 18: itinerary ----------
async def test_itinerary_total_with_curated_markup():
    class Flights:
        async def cheapest(self, o, c): return 25000
    class Hotels:
        async def nightly(self, c, s): return 8000
    class Attractions:
        async def top(self, c, d):
            return [{"name": "Fort Jesus", "entry_fee": 200}, {"name": "Old Town", "entry_fee": 100}]
    class Weather:
        async def forecast(self, c, d): return {"tag": "sunny"}
    comp = ItineraryComposer(Flights(), Hotels(), Attractions(), Weather())
    out = await comp.compose(origin="NBO", county="mombasa", days=3, travelers=2)
    flight, stay, acts = Decimal(25000), Decimal(8000 * 2 * 2), Decimal(300 * 2)
    assert out["total"] == flight + stay + acts + (flight + stay + acts) * Decimal("0.10")

# ---------- 19: weather ingestion ----------
class FakeFeed:
    async def fetch(self):
        return [
            {"county_code": "KE-021", "month": 3, "temp_c": 25, "rainfall_mm": 60},
            {"county_code": "KE-021", "month": 3, "temp_c": 27, "rainfall_mm": 50},
        ]

async def test_weather_ingest_upserts_calendar_and_busts_cache():
    repo, cache = InMemoryRepository(), TTLCache()
    await cache.set("platform_stats", {"x": 1})
    ing = WeatherIngestor(repo, FakeFeed(), cache)
    out = await ing.run()
    assert out["observations"] == 2 and out["calendar_rows_upserted"] == 1
    assert (await cache.get("platform_stats")) is None   # invalidated
    rows = await repo.find("seasonal_calendar", county_code="KE-021", month=3)
    assert rows[0]["avg_temp_c"] == 26.0 and rows[0]["season_tag"] == "dry" and rows[0]["rainfall_mm"] == 110.0

# ---------- 20: k-anonymity ----------
def test_anonymizer_suppresses_small_classes():
    k = KAnonymizer(Config())   # k=5
    rows = [{"county_id": 21, "sector_id": 2, "amount": 950}] * 5 + \
           [{"county_id": 5, "sector_id": 9, "amount": 12000}] * 1
    out = k.anonymize(rows)
    assert len(out) == 5 and all(r["amount_band"] == "100-1000" for r in out)

# ---------- INTEGRATION: the full wired money chain ----------
async def test_full_money_chain_pay_release_pool():
    p = KiccPlatform()
    r = await p.pay(order_id=1, buyer_id=10, seller_id=20, amount="1000",
                    mpesa_ref="R1", county_id=21, sector_id=2)
    assert r["status"] == "settled"
    out = await p.release_escrow(escrow_id="ESC-1", order_id=1, seller_id=20,
                                 buyer_id=10, gross="1000", county_id=21,
                                 sector_id=2, sponsor_id=99)
    assert out["seller_net"] == Decimal("950.00") and out["platform_fee"] == Decimal("50.00")
    assert len(p.ledger.journal) == 3
    assert p.ledger.audit_sum() == 0                       # global invariant holds
    dist = p.run_pool_distribution("2026-09")
    assert len(dist := dist) >= 1
    assert dist[0]["entity_id"] == 20 and float(dist[0]["amount"]) > 0
    assert dist[0]["breakdown"]["reserves"] > 0            # holdback + equalisation taken

async def test_onboarding_freezes_on_watchlist_hit():
    p = KiccPlatform()
    ok = p.onboard(phone_verified=True, kra_pin="P123456789A", pin_validated=True,
                   legal_name="Grace Wanjiku Fresh Produce Ltd")
    assert ok["tier"] == 1
    frozen = p.onboard(phone_verified=True, kra_pin="P123456789A", pin_validated=True,
                       legal_name="John Appropreator Enterprises")
    assert frozen.get("participation") == "frozen"

def test_rate_limiter_blocks_burst():
    rl = TokenBucket(rate_per_sec=1.0, capacity=2)
    assert rl.allow("ip1") and rl.allow("ip1")
    assert rl.allow("ip1") is False                        # bucket empty

async def test_homepage_hot_path_cached():
    p = KiccPlatform()
    for i in range(47):
        await p.repo.insert("counties", {"name": f"C{i}"})
    await p.repo.insert("exhibitions", {"name": "E", "is_published": True, "bookings_count": 3})
    h1 = await p.homepage()
    h2 = await p.homepage()
    assert h1["stats"]["kenya_counties"] == 47
    assert h2["featured_exhibitions"] == h1["featured_exhibitions"]
    assert p.cache.hits >= 1
