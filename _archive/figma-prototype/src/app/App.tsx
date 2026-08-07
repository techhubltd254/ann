import { useState, useRef, useCallback, useEffect } from "react";
import { motion, AnimatePresence, useScroll, useTransform } from "motion/react";
import {
  Search, ChevronLeft, XCircle, Heart, Printer, MonitorPlay, Play,
  Building2, TreePine, Stethoscope, Wheat, Hotel,
  ShoppingCart, Star, ArrowRight, CheckCircle, Clock, ChevronDown,
  Calendar, MapPin, Package, Users, Layers, Phone, Mail, Globe,
  Shield, Trash2, Wifi, QrCode, LayoutDashboard, BarChart3,
  Eye, Download,
} from "lucide-react";
import kiccBg from "../imports/kicc.jpg";
import {
  type Page,
  KICC_STATS, COUNTIES, PRODUCTS, VENUES, EXHIBITIONS, ENTITIES, SCREENS,
  u, fmt,
  Counter, TiltCard, PageWrap, Reveal, Pill, Btn,
  KICCLogo, Nav, Footer, SectionHead,
} from "./shared";
import { DashBuyer, DashExhibitor, DashSeller, DashCounty, DashAdmin } from "./dashboards";
import { DirectoryPage, ExhibitorPublicProfile } from "./directory";
import { login, logout, getAccessToken } from "../lib/api";
import * as api from "../lib/data";

// ─── Local data ───────────────────────────────────────────────────────────────
const SECTORS = [
  { id: "tourism",       name: "Tourism & Hospitality", icon: Hotel,        entities: 38 },
  { id: "agriculture",   name: "Agriculture & Food",    icon: Wheat,        entities: 24 },
  { id: "health",        name: "Health & Wellness",     icon: Stethoscope,  entities: 19 },
  { id: "energy",        name: "Energy & Environment",  icon: Building2,    entities: 11 },
  { id: "tech",          name: "Technology & ICT",      icon: Globe,        entities: 22 },
  { id: "manufacturing", name: "Manufacturing",         icon: Building2,    entities: 15 },
  { id: "forestry",      name: "Forestry & Wildlife",   icon: TreePine,     entities: 9  },
  { id: "services",      name: "Professional Services", icon: Package,      entities: 27 },
];

const LIVE_PLANS = [
  {
    id: "starter", name: "Starter Broadcast", price: 5000, period: "per event", color: "#2D6A4F", highlight: false,
    desc: "Perfect for small conferences, workshops, and county meetings.",
    features: ["Up to 500 concurrent viewers","HD 720p stream quality","4-hour maximum duration","KICC-branded viewer page","Mobile & desktop playback","Basic chat for viewers","Post-event replay (7 days)"],
  },
  {
    id: "professional", name: "Professional Live", price: 15000, period: "per event", color: "#901C1E", highlight: true,
    desc: "For trade fairs, product launches, and county-wide broadcasts.",
    features: ["Up to 5,000 concurrent viewers","Full HD 1080p quality","8-hour maximum duration","Custom-branded viewer portal","Multi-platform simulcast","Live Q&A and polling","Full recording + download","Real-time analytics dashboard","Dedicated stream engineer"],
  },
  {
    id: "enterprise", name: "Enterprise Broadcast", price: 45000, period: "per event", color: "#0B1E57", highlight: false,
    desc: "For major national and international exhibitions with global reach.",
    features: ["Unlimited concurrent viewers","4K Ultra HD stream","24-hour continuous stream","Multi-camera production","White-label viewer app","Translation & captions (3 langs)","Permanent VOD archive","Priority CDN delivery","Dedicated production crew","API & embed integration"],
  },
];

const LIVE_MONTHLY = [
  { name: "Creator", price: 8000, events: 3, viewers: "500/event", color: "#2D6A4F" },
  { name: "Pro Broadcaster", price: 25000, events: "Unlimited", viewers: "10,000/event", color: "#901C1E" },
  { name: "County Package", price: 60000, events: "Unlimited", viewers: "Unlimited", color: "#0B1E57" },
];

const LIVE_NOW = [
  { id: "l1", title: "Nairobi Tech Summit — Keynote", county: "Nairobi", viewers: 1240, img: "1741991110666-88115e724741", live: true },
  { id: "l2", title: "Kilifi Coastal Farmers Forum", county: "Kilifi", viewers: 380, img: "1726397461856-1c6f801746ef", live: true },
  { id: "l3", title: "KICC Trade Fair Opening Ceremony", county: "Nairobi", viewers: 4820, img: "1775314054195-85f31de0c944", live: true },
  { id: "l4", title: "Nakuru Dairy & Agri Expo", county: "Nakuru", viewers: 614, img: "1547471080-7cc2caa01a7e", live: false },
];

// ─── COUNTY STRIP ─────────────────────────────────────────────────────────────
function CountyStrip({ navigate }: { navigate: (p: Page) => void }) {
  const [query, setQuery] = useState("");
  const [activeRegion, setActiveRegion] = useState("All");
  const stripRef = useRef<HTMLDivElement>(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(true);

  const regions = ["All", "Central", "Coast", "Eastern", "Nyanza", "North Eastern", "Rift Valley", "Western"];
  const filtered = COUNTIES.filter(c => {
    const matchQ = c.name.toLowerCase().includes(query.toLowerCase()) || c.tagline.toLowerCase().includes(query.toLowerCase());
    const matchR = activeRegion === "All" || c.region === activeRegion;
    return matchQ && matchR;
  });

  const updateScrollState = () => {
    const el = stripRef.current; if (!el) return;
    setCanScrollLeft(el.scrollLeft > 8);
    setCanScrollRight(el.scrollLeft < el.scrollWidth - el.clientWidth - 8);
  };
  const scroll = (dir: "left" | "right") => {
    const el = stripRef.current; if (!el) return;
    el.scrollBy({ left: dir === "right" ? 320 : -320, behavior: "smooth" });
  };

  return (
    <section className="py-20 overflow-hidden">
      <div className="max-w-7xl mx-auto px-5">
        <Reveal>
          <SectionHead eyebrow="Explore Kenya" title={<>Browse all <span className="text-[#FFCD05]">47 Counties</span></>}
            sub="Search by name or filter by region — then click to explore sectors and businesses." />
        </Reveal>
        <Reveal delay={0.08}>
          <div className="flex flex-col sm:flex-row gap-3 mb-6">
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 text-white/30" size={15} />
              <input value={query} onChange={e => setQuery(e.target.value)} placeholder="Search county name…"
                className="w-full pl-10 pr-4 h-11 rounded-xl bg-[#141B2E] border border-white/10 text-white/80 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05] placeholder:text-white/25 transition-all" />
              {query && <button onClick={() => setQuery("")} className="absolute right-3 top-1/2 -translate-y-1/2 text-white/30 hover:text-white cursor-pointer">×</button>}
            </div>
            <div className="flex gap-1.5 overflow-x-auto pb-1 flex-wrap sm:flex-nowrap">
              {regions.map(r => (
                <button key={r} onClick={() => setActiveRegion(r)}
                  className={`shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-bold cursor-pointer transition-all ${activeRegion === r ? "bg-[#901C1E] text-white" : "bg-[#141B2E] text-white/40 border border-white/8 hover:border-white/20 hover:text-white"}`}>
                  {r}
                </button>
              ))}
            </div>
          </div>
        </Reveal>
        <div className="flex items-center justify-between mb-3">
          <span className="text-white/30 text-xs font-semibold">{filtered.length} {filtered.length === 1 ? "county" : "counties"}</span>
          <div className="flex gap-2">
            <button onClick={() => scroll("left")} disabled={!canScrollLeft}
              className={`w-8 h-8 rounded-full border flex items-center justify-center cursor-pointer transition-all ${canScrollLeft ? "border-white/20 text-white/60 hover:border-[#FFCD05] hover:text-[#FFCD05]" : "border-white/8 text-white/15 cursor-not-allowed"}`}>
              <ChevronLeft size={15} />
            </button>
            <button onClick={() => scroll("right")} disabled={!canScrollRight}
              className={`w-8 h-8 rounded-full border flex items-center justify-center cursor-pointer transition-all ${canScrollRight ? "border-white/20 text-white/60 hover:border-[#FFCD05] hover:text-[#FFCD05]" : "border-white/8 text-white/15 cursor-not-allowed"}`}>
              ›
            </button>
          </div>
        </div>
      </div>
      <div className="relative">
        {canScrollLeft && <div className="absolute left-0 top-0 bottom-0 w-16 bg-gradient-to-r from-[#07090F] to-transparent z-10 pointer-events-none" />}
        {canScrollRight && <div className="absolute right-0 top-0 bottom-0 w-16 bg-gradient-to-l from-[#07090F] to-transparent z-10 pointer-events-none" />}
        <div ref={stripRef} onScroll={updateScrollState} className="flex gap-4 overflow-x-auto pb-4 pl-5 pr-5" style={{ scrollbarWidth: "none", WebkitOverflowScrolling: "touch" }}>
          <AnimatePresence mode="popLayout">
            {filtered.length === 0 ? (
              <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="flex-1 flex flex-col items-center justify-center py-16 text-center min-w-[300px]">
                <MapPin size={32} className="text-white/15 mb-3" />
                <div className="text-white/40 font-semibold">No counties match "{query}"</div>
                <button onClick={() => { setQuery(""); setActiveRegion("All"); }} className="mt-3 text-[#FFCD05] text-sm underline cursor-pointer">Clear filters</button>
              </motion.div>
            ) : (
              filtered.map((c, i) => (
                <motion.div key={c.id} layout initial={{ opacity: 0, scale: 0.92 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.88 }}
                  transition={{ duration: 0.28, delay: Math.min(i * 0.025, 0.25) }} className="shrink-0" style={{ width: 200 }}>
                  <TiltCard>
                    <motion.button onClick={() => navigate("county")} whileHover={{ y: -6 }} transition={{ duration: 0.22 }}
                      className="group relative overflow-hidden rounded-2xl w-full cursor-pointer block text-left" style={{ height: 280 }}>
                      <img src={u(c.img, 300, 400)} alt={c.name} className="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" />
                      <div className="absolute inset-0 bg-gradient-to-t from-black/95 via-black/30 to-transparent" />
                      <div className="absolute top-3 left-3"><span className="text-[9px] font-bold text-white/60 bg-black/40 backdrop-blur-sm px-2 py-1 rounded-full tracking-wide">{c.region}</span></div>
                      <div className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                        <div className="w-6 h-6 bg-[#FFCD05] rounded-full flex items-center justify-center"><ArrowRight size={11} className="text-[#07090F]" /></div>
                      </div>
                      <div className="absolute inset-x-0 bottom-0 p-4">
                        <div className="text-white font-black text-base leading-tight">{c.name}</div>
                        <div className="text-white/50 text-[11px] mt-1 leading-snug">{c.tagline}</div>
                      </div>
                    </motion.button>
                  </TiltCard>
                </motion.div>
              ))
            )}
          </AnimatePresence>
        </div>
      </div>
    </section>
  );
}

// ─── HOME PAGE ────────────────────────────────────────────────────────────────
function HomePage({ navigate }: { navigate: (p: Page) => void }) {
  const { scrollY } = useScroll();
  const bgY = useTransform(scrollY, [0, 600], ["0%", "30%"]);
  const bgO = useTransform(scrollY, [0, 400], [0.55, 0.85]);

  return (
    <PageWrap>
      <section className="relative min-h-screen flex items-center overflow-hidden">
        <motion.img src={kiccBg} alt="KICC Tower" className="absolute inset-0 w-full h-full object-cover" style={{ y: bgY }} />
        <motion.div className="absolute inset-0 bg-gradient-to-r from-[#07090F] via-[#07090F]/80 to-transparent" style={{ opacity: bgO }} />
        <div className="absolute inset-0 bg-gradient-to-t from-[#07090F] via-transparent to-transparent" />
        <div className="relative max-w-7xl mx-auto px-5 pt-28 pb-20 w-full grid md:grid-cols-2 gap-10 items-center">
          <div>
            <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.6 }} className="flex items-center gap-3 mb-6">
              <div className="h-px w-10 bg-[#FFCD05]" />
              <Pill color="gold">Africa's Premier Meeting Venue — Global Exhibition Platform</Pill>
            </motion.div>
            <motion.h1 initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.7, delay: 0.1 }}
              className="text-5xl md:text-7xl font-black text-white leading-[1.0] tracking-tight">
              Kenya's<br /><span className="text-[#FFCD05]">Digital</span><br />Economy<br />Gateway
            </motion.h1>
            <motion.p initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.6, delay: 0.25 }}
              className="text-white/55 text-lg leading-relaxed mt-6 max-w-md">
              From 47 county markets to world-class exhibition halls — KICC connects Kenya's entire economy on one platform.
            </motion.p>
            <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.6, delay: 0.4 }} className="mt-8 flex flex-wrap gap-3">
              <Btn onClick={() => navigate("county")} variant="primary" size="lg">Explore Counties <ArrowRight size={18} /></Btn>
              <Btn onClick={() => navigate("exhibition")} variant="outline-light" size="lg">Book Exhibition</Btn>
            </motion.div>
          </div>
          <motion.div initial={{ opacity: 0, x: 40 }} animate={{ opacity: 1, x: 0 }} transition={{ duration: 0.7, delay: 0.35 }} className="grid grid-cols-2 gap-4">
            {KICC_STATS.map((s, i) => (
              <TiltCard key={s.label}>
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.5 + i * 0.1 }}
                  className="bg-white/5 backdrop-blur-sm border border-white/12 rounded-2xl p-6 hover:border-[#FFCD05]/40 transition-colors">
                  <div className="text-4xl font-black text-[#FFCD05]"><Counter to={s.value} suffix={s.suffix} /></div>
                  <div className="text-white/50 text-xs font-medium mt-2 leading-snug">{s.label}</div>
                </motion.div>
              </TiltCard>
            ))}
          </motion.div>
        </div>
        <motion.div animate={{ y: [0, 10, 0] }} transition={{ repeat: Infinity, duration: 2 }}
          className="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-white/30">
          <div className="text-[10px] tracking-[0.2em] uppercase font-semibold">Scroll</div>
          <ChevronDown size={16} />
        </motion.div>
      </section>

      <CountyStrip navigate={navigate} />

      <section className="border-y border-white/8 py-20 bg-[#0D1220]">
        <div className="max-w-7xl mx-auto px-5">
          <Reveal>
            <div className="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
              <SectionHead eyebrow="KICC Marketplace" title={<>Authentic Kenyan<br /><span className="text-[#FFCD05]">Products</span></>} sub="Directly from county producers across Kenya." />
              <Btn onClick={() => navigate("marketplace")} variant="dark" size="sm">Browse all <ArrowRight size={14} /></Btn>
            </div>
          </Reveal>
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {PRODUCTS.map((p, i) => (
              <Reveal key={p.id} delay={i * 0.07}>
                <TiltCard>
                  <motion.button onClick={() => navigate("product")} whileHover={{ y: -4 }} transition={{ duration: 0.25 }}
                    className="group bg-[#141B2E] rounded-2xl overflow-hidden border border-white/8 hover:border-[#FFCD05]/30 transition-all w-full text-left cursor-pointer block">
                    <div className="aspect-square overflow-hidden bg-[#0D1220]">
                      <img src={u(p.img, 400, 400)} alt={p.name} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                    </div>
                    <div className="p-4">
                      <div className="text-[10px] font-bold text-[#FFCD05] uppercase tracking-widest mb-1">{p.county} · {p.category}</div>
                      <div className="font-bold text-white text-sm leading-snug line-clamp-2 mb-2">{p.name}</div>
                      <div className="flex items-center gap-1 mb-3"><Star size={11} className="text-[#FFCD05] fill-[#FFCD05]" /><span className="text-xs font-semibold text-white">{p.rating}</span><span className="text-xs text-white/35">({p.reviews})</span></div>
                      <div className="font-black text-[#FFCD05] text-base">KES {fmt(p.price)}</div>
                    </div>
                  </motion.button>
                </TiltCard>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <section className="max-w-7xl mx-auto px-5 py-20">
        <Reveal>
          <SectionHead eyebrow="Events" title={<>Upcoming<br /><span className="text-[#FFCD05]">Exhibitions</span></>}
            sub="Book booths, showcase products, and connect with buyers across East Africa." />
        </Reveal>
        <div className="grid md:grid-cols-3 gap-5">
          {EXHIBITIONS.map((ex, i) => (
            <Reveal key={ex.id} delay={i * 0.1}>
              <TiltCard>
                <div onClick={() => navigate("exhibition")} className="group bg-[#0D1220] rounded-2xl overflow-hidden border border-white/8 hover:border-[#901C1E]/50 transition-all cursor-pointer">
                  <div className="h-44 overflow-hidden bg-[#141B2E]">
                    <img src={u(ex.img, 600, 350)} alt={ex.name} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-600 opacity-80" />
                  </div>
                  <div className="p-5">
                    <h3 className="font-black text-white text-base mt-3 leading-snug">{ex.name}</h3>
                    <div className="mt-3 space-y-1.5 text-xs text-white/45">
                      <div className="flex items-center gap-2"><Calendar size={12} />{ex.dates}</div>
                      <div className="flex items-center gap-2"><MapPin size={12} />{ex.venue}</div>
                    </div>
                    <div className="mt-4 flex items-center justify-between">
                      <div className="font-black text-[#FFCD05] text-lg">{ex.price}<span className="text-xs font-normal text-white/35">/booth</span></div>
                      <span className="text-xs text-white/50 group-hover:text-[#FFCD05] transition-colors flex items-center gap-1">Details <ArrowRight size={11} /></span>
                    </div>
                  </div>
                </div>
              </TiltCard>
            </Reveal>
          ))}
        </div>
      </section>

      <section className="bg-[#0D1220] border-y border-white/8 py-20">
        <div className="max-w-7xl mx-auto px-5">
          <Reveal>
            <div className="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
              <SectionHead eyebrow="KICC Facilities" title={<>World-Class<br /><span className="text-[#FFCD05]">Venues</span></>} sub="From intimate boardrooms to the 2,000-capacity Tsavo Hall." />
              <Btn onClick={() => navigate("venue")} variant="dark" size="sm">All venues <ArrowRight size={14} /></Btn>
            </div>
          </Reveal>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {(VENUES as any[]).slice(0, 4).map((v: any, i: number) => (
              <Reveal key={v.id} delay={i * 0.08}>
                <TiltCard>
                  <motion.div onClick={() => navigate("venue")} whileHover={{ y: -4 }} transition={{ duration: 0.2 }}
                    className="group bg-[#141B2E] rounded-2xl overflow-hidden border border-white/8 hover:border-[#FFCD05]/40 cursor-pointer transition-all">
                    <div className="h-36 overflow-hidden bg-[#0D1220]">
                      <img src={u(v.fallback || v.img || "1775314054195-85f31de0c944", 400, 250)} alt={v.name}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-80" />
                    </div>
                    <div className="p-4">
                      <div className="font-black text-white text-sm">{v.name}</div>
                      <div className="text-white/40 text-xs mt-1">{v.capacity} capacity · {v.area || (v.sqm ? `${v.sqm}m²` : "")}</div>
                      <div className="mt-3 flex items-center justify-between">
                        <span className="text-[#FFCD05] font-bold text-sm">{v.priceDay || v.price}</span>
                        <ArrowRight size={14} className="text-white/30 group-hover:text-[#FFCD05] transition-colors" />
                      </div>
                    </div>
                  </motion.div>
                </TiltCard>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <section className="max-w-7xl mx-auto px-5 py-20">
        <Reveal>
          <div className="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#0D1220] to-[#07090F]">
            <img src={u("1741991109886-90e70988f27b", 1200, 500)} alt="" className="absolute inset-0 w-full h-full object-cover opacity-20" />
            <div className="relative px-10 py-16 md:py-20 flex flex-col md:flex-row items-center justify-between gap-8">
              <div>
                <Pill color="gold">18 Digital Screens</Pill>
                <h2 className="text-3xl md:text-5xl font-black text-white mt-4 leading-tight">Advertise on<br /><span className="text-[#FFCD05]">Kenya's Most</span><br />Iconic Screens</h2>
                <p className="text-white/45 mt-4 max-w-md text-sm leading-relaxed">The KICC tower rooftop LED, Uhuru Highway mega billboard, and 16 more premium screens reaching millions daily.</p>
              </div>
              <div className="flex flex-col gap-3 shrink-0">
                <Btn onClick={() => navigate("screens")} variant="gold" size="lg">Book a Screen</Btn>
                <Btn onClick={() => navigate("screens")} variant="outline-light" size="md">View all 18 screens</Btn>
              </div>
            </div>
          </div>
        </Reveal>
      </section>
    </PageWrap>
  );
}

// ─── COUNTY PAGE ──────────────────────────────────────────────────────────────
function CountyPage({ navigate }: { navigate: (p: Page) => void }) {
  const [active, setActive] = useState(COUNTIES[0]);
  return (
    <PageWrap>
      <div className="pt-20 min-h-screen">
        <div className="relative h-80 overflow-hidden">
          <motion.img key={active.id} src={u(active.img, 1400, 700)} alt={active.name} initial={{ opacity: 0, scale: 1.05 }} animate={{ opacity: 1, scale: 1 }} transition={{ duration: 0.6 }} className="w-full h-full object-cover" />
          <div className="absolute inset-0 bg-gradient-to-t from-[#07090F] via-[#07090F]/50 to-transparent" />
          <div className="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-8">
            <button onClick={() => navigate("home")} className="flex items-center gap-1.5 text-white/50 hover:text-white text-sm mb-3 cursor-pointer transition-colors"><ChevronLeft size={15} /> Back</button>
            <h1 className="text-4xl font-black text-white">{active.name} County</h1>
            <p className="text-white/50 mt-1">{active.tagline}</p>
          </div>
        </div>
        <div className="border-b border-white/8 bg-[#0D1220]">
          <div className="max-w-7xl mx-auto px-5 flex gap-2 overflow-x-auto py-3">
            {COUNTIES.map(c => (
              <button key={c.id} onClick={() => setActive(c)}
                className={`shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${active.id === c.id ? "bg-[#901C1E] text-white" : "text-white/50 hover:text-white hover:bg-white/8"}`}>
                {c.name}
              </button>
            ))}
          </div>
        </div>
        <div className="max-w-7xl mx-auto px-5 py-12">
          <div className="flex items-center justify-between mb-8">
            <SectionHead eyebrow={active.name} title={<>Explore <span className="text-[#FFCD05]">Sectors</span></>} />
            <Btn onClick={() => navigate("marketplace")} variant="primary" size="sm">View Products <ArrowRight size={14} /></Btn>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            {SECTORS.map((s, i) => (
              <Reveal key={s.id} delay={i * 0.05}>
                <TiltCard>
                  <motion.button onClick={() => navigate("sector")} whileHover={{ y: -3 }}
                    className="group w-full bg-[#0D1220] border border-white/8 hover:border-[#FFCD05]/40 rounded-2xl p-6 text-center cursor-pointer transition-all block">
                    <div className="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-white/5 group-hover:bg-[#FFCD05]/10 transition-colors">
                      <s.icon size={24} className="text-[#FFCD05]" />
                    </div>
                    <div className="font-bold text-white text-sm leading-snug">{s.name}</div>
                    <div className="text-white/35 text-xs mt-1">{s.entities} entities</div>
                  </motion.button>
                </TiltCard>
              </Reveal>
            ))}
          </div>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── SECTOR PAGE ──────────────────────────────────────────────────────────────
function SectorPage({ navigate }: { navigate: (p: Page) => void }) {
  const [filter, setFilter] = useState("All");
  return (
    <PageWrap>
      <div className="pt-20">
        <div className="bg-[#0D1220] border-b border-white/8 py-12">
          <div className="max-w-7xl mx-auto px-5">
            <button onClick={() => navigate("county")} className="flex items-center gap-1.5 text-white/40 hover:text-white text-sm mb-4 cursor-pointer"><ChevronLeft size={15} /> Nairobi County</button>
            <div className="flex items-center gap-5">
              <div className="w-16 h-16 bg-[#901C1E]/20 border border-[#901C1E]/30 rounded-2xl flex items-center justify-center shrink-0"><Hotel size={28} className="text-[#901C1E]" /></div>
              <div><h1 className="text-3xl font-black text-white">Tourism & Hospitality</h1><p className="text-white/40 mt-1 text-sm">38 registered entities · Nairobi County</p></div>
            </div>
          </div>
        </div>
        <div className="max-w-7xl mx-auto px-5 py-10">
          <div className="flex flex-wrap gap-2 mb-8">
            {["All", "Hotels", "Attractions", "Wildlife", "Experiences"].map(f => (
              <button key={f} onClick={() => setFilter(f)}
                className={`px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition-all ${filter === f ? "bg-[#901C1E] text-white" : "bg-[#141B2E] text-white/50 hover:text-white border border-white/8 hover:border-white/20"}`}>
                {f}
              </button>
            ))}
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            {ENTITIES.map((e, i) => (
              <Reveal key={e.id} delay={i * 0.07}>
                <TiltCard>
                  <motion.button onClick={() => navigate("entity")} whileHover={{ y: -4 }}
                    className="group w-full bg-[#0D1220] rounded-2xl overflow-hidden border border-white/8 hover:border-[#FFCD05]/40 cursor-pointer text-left block transition-all">
                    <div className="h-44 overflow-hidden bg-[#141B2E]">
                      <img src={u(e.img, 400, 280)} alt={e.name} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                    </div>
                    <div className="p-4">
                      <Pill color="gold">{e.tag}</Pill>
                      <div className="font-bold text-white text-sm mt-2 leading-snug line-clamp-2">{e.name}</div>
                      <div className="text-white/35 text-xs mt-1">{e.type}</div>
                      <div className="flex items-center gap-1 mt-2"><Star size={11} className="text-[#FFCD05] fill-[#FFCD05]" /><span className="text-xs font-semibold text-white">{e.rating}</span></div>
                      <div className="font-bold text-[#FFCD05] text-sm mt-2">{e.price}</div>
                    </div>
                  </motion.button>
                </TiltCard>
              </Reveal>
            ))}
          </div>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── ENTITY DETAIL ────────────────────────────────────────────────────────────
function EntityDetail({ navigate }: { navigate: (p: Page) => void }) {
  const e = ENTITIES[0];
  const [imgIdx, setImgIdx] = useState(0);
  const imgs = [e.img, "1523805009345-7448845a9e53", "1618856445394-259e67220916", "1579005318686-5a86bbb3bf03"];
  return (
    <PageWrap>
      <div className="pt-20 max-w-7xl mx-auto px-5 py-10">
        <button onClick={() => navigate("sector")} className="flex items-center gap-1.5 text-white/40 hover:text-white text-sm mb-6 cursor-pointer"><ChevronLeft size={15} /> Tourism & Hospitality</button>
        <div className="grid lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2">
            <div className="rounded-2xl overflow-hidden h-80 bg-[#141B2E]">
              <motion.img key={imgIdx} src={u(imgs[imgIdx], 900, 500)} alt={e.name} initial={{ opacity: 0 }} animate={{ opacity: 1 }} transition={{ duration: 0.4 }} className="w-full h-full object-cover" />
            </div>
            <div className="flex gap-2 mt-3">
              {imgs.map((img, i) => (
                <button key={i} onClick={() => setImgIdx(i)} className={`w-20 h-14 rounded-xl overflow-hidden border-2 cursor-pointer shrink-0 transition-all ${imgIdx === i ? "border-[#FFCD05]" : "border-white/10"}`}>
                  <img src={u(img, 160, 120)} alt="" className="w-full h-full object-cover" />
                </button>
              ))}
            </div>
            <div className="mt-8">
              <h1 className="text-3xl font-black text-white">{e.name}</h1>
              <div className="flex flex-wrap items-center gap-3 mt-3">
                <Pill color="gold">{e.tag}</Pill>
                <span className="flex items-center gap-1 text-sm text-white/60"><Star size={13} className="text-[#FFCD05] fill-[#FFCD05]" />{e.rating} rating</span>
                <span className="flex items-center gap-1.5 text-sm text-white/40"><MapPin size={13} /> Nairobi, Kenya</span>
              </div>
              <p className="mt-5 text-white/50 leading-relaxed text-sm">
                Hemingways Nairobi is an all-suite boutique hotel nestled in the leafy suburb of Karen. Inspired by Ernest Hemingway, the hotel offers 45 suites, three restaurants, a full spa, and personalised safari experiences. A member of Relais & Châteaux.
              </p>
            </div>
          </div>
          <div>
            <div className="bg-[#0D1220] border border-white/10 rounded-2xl p-6 sticky top-24">
              <div className="font-black text-[#FFCD05] text-2xl">{e.price}</div>
              <div className="text-white/35 text-sm">incl. breakfast</div>
              <div className="mt-6 space-y-3">
                <Btn variant="primary" className="w-full" onClick={() => navigate("hotel-booking")}>Book Stay</Btn>
                <Btn variant="dark" className="w-full">Request Quote</Btn>
              </div>
              <div className="mt-6 pt-5 border-t border-white/8 space-y-3">
                {[[Phone, "+254 20 363 0000"], [Mail, "reservations@hemingways.com"], [MapPin, "Karen, Nairobi"]].map(([Icon, val], i) => (
                  <div key={i} className="flex items-center gap-3 text-sm text-white/40">
                    {/* @ts-ignore */}
                    <Icon size={13} className="text-[#FFCD05] shrink-0" />
                    <span>{val as string}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── MARKETPLACE ──────────────────────────────────────────────────────────────
function MarketplacePage({ navigate, addToCart }: { navigate: (p: Page) => void; addToCart: () => void }) {
  const [search, setSearch] = useState("");
  const [cat, setCat] = useState("All");
  const [prods, setProds] = useState<any[]>([]);
  const [countyMap, setCountyMap] = useState<Record<number,string>>({});
  useEffect(() => {
    fetch('http://localhost:8091/api/marketplace/products').then(r => r.json()).then(setProds).catch(() => {});
    fetch('http://localhost:8091/api/data/public/counties').then(r => r.json()).then((c: any[]) => {
      const m: Record<number,string> = {};
      c.forEach(x => m[x.id] = x.name);
      setCountyMap(m);
    }).catch(() => {});
  }, []);
  const cats = ["All", ...Array.from(new Set(prods.map(p => p.category).filter(Boolean)))];
  const filtered = prods.filter(p => {
    if (cat !== "All" && p.category !== cat) return false;
    if (search && !p.name?.toLowerCase().includes(search.toLowerCase())) return false;
    return true;
  });
  return (
    <PageWrap>
      <div className="pt-20 max-w-7xl mx-auto px-5 py-10">
        <SectionHead eyebrow="KICC Marketplace" title={<>Shop<br /><span className="text-[#FFCD05]">Kenya's Finest</span></>} sub="Authentic county products, verified sellers, real-time inventory." />
        <div className="flex flex-col md:flex-row gap-3 mb-8">
          <div className="relative flex-1">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-white/30" size={15} />
            <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search products…"
              className="w-full pl-10 pr-4 h-11 rounded-xl bg-[#141B2E] border border-white/10 text-white/80 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05] placeholder:text-white/25" />
          </div>
          <div className="flex gap-2 overflow-x-auto pb-1">
            {cats.slice(0,8).map(c => (
              <button key={c} onClick={() => setCat(c)}
                className={`shrink-0 px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition-all ${cat === c ? "bg-[#901C1E] text-white" : "bg-[#141B2E] text-white/50 border border-white/8 hover:border-white/25 hover:text-white"}`}>
                {c}
              </button>
            ))}
          </div>
        </div>
        {prods.length === 0 && <p className="text-white/40 text-center py-20">Loading products from all 47 counties...</p>}
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          {filtered.map((p, i) => (
            <Reveal key={`${p.id}-${i}`} delay={Math.min(i * 0.04, 0.3)}>
              <TiltCard>
                <motion.button onClick={() => { addToCart(); try { api.addToCart(p.id, 1) } catch {}; }} whileHover={{ y: -4 }}
                  className="group bg-[#0D1220] rounded-2xl overflow-hidden border border-white/8 hover:border-[#FFCD05]/35 cursor-pointer text-left w-full block transition-all">
                  <div className="aspect-square overflow-hidden bg-[#141B2E] flex items-center justify-center">
                    <div className="text-4xl">{p.category === 'Food & Beverages' ? '🥫' : p.category === 'Crafts & Textiles' ? '🧵' : p.category === 'Beauty & Wellness' ? '💆' : p.category === 'Agriculture' ? '🌾' : '📦'}</div>
                  </div>
                  <div className="p-4">
                    <div className="text-[9px] font-bold text-[#FFCD05] uppercase tracking-widest mb-1">{countyMap[p.countyId] || `County #${p.countyId}`}</div>
                    <div className="font-bold text-white text-sm leading-snug line-clamp-2 mb-2">{p.name}</div>
                    <div className="flex items-center justify-between">
                      <span className="font-black text-[#FFCD05]">KES {p.price?.toLocaleString()}</span>
                      <span className="text-xs text-white/35">{p.stock != null ? `${p.stock} avail` : 'In stock'}</span>
                    </div>
                  </div>
                </motion.button>
              </TiltCard>
            </Reveal>
          ))}
        </div>
      </div>
    </PageWrap>
  );
}

// ─── PRODUCT DETAIL ───────────────────────────────────────────────────────────
function ProductDetail({ navigate, addToCart }: { navigate: (p: Page) => void; addToCart: () => void }) {
  const p = PRODUCTS[0];
  const [qty, setQty] = useState(1);
  const [variant, setVariant] = useState("250g");
  return (
    <PageWrap>
      <div className="pt-20 max-w-7xl mx-auto px-5 py-10">
        <button onClick={() => navigate("marketplace")} className="flex items-center gap-1.5 text-white/40 hover:text-white text-sm mb-6 cursor-pointer"><ChevronLeft size={15} /> Marketplace</button>
        <div className="grid lg:grid-cols-2 gap-10">
          <div className="rounded-2xl overflow-hidden aspect-square bg-[#141B2E]">
            <img src={u(p.img, 700, 700)} alt={p.name} className="w-full h-full object-cover" />
          </div>
          <div>
            <div className="text-[#FFCD05] text-xs font-bold uppercase tracking-widest">{p.county} · {p.category}</div>
            <h1 className="text-3xl font-black text-white mt-2 leading-tight">{p.name}</h1>
            <div className="flex items-center gap-3 mt-3">
              <div className="flex gap-0.5">{[1,2,3,4,5].map(s => <Star key={s} size={13} className="text-[#FFCD05] fill-[#FFCD05]" />)}</div>
              <span className="text-sm font-semibold text-white">{p.rating}</span>
              <span className="text-sm text-white/35">({p.reviews} reviews)</span>
            </div>
            <div className="text-4xl font-black text-[#FFCD05] mt-5">KES {fmt(p.price * qty)}</div>
            <p className="text-white/45 mt-4 text-sm leading-relaxed">Premium single-origin Kenyan AA coffee from Nyeri's high-altitude farms. Hand-picked, naturally processed, and expertly roasted in small batches. Bright acidity, stone-fruit notes, and a clean honey finish.</p>
            <div className="mt-6">
              <div className="text-xs font-bold text-white/50 uppercase tracking-wider mb-3">Weight</div>
              <div className="flex flex-wrap gap-2">
                {["250g", "500g", "1kg", "2kg"].map(v => (
                  <button key={v} onClick={() => setVariant(v)}
                    className={`px-5 py-2.5 rounded-xl border-2 text-sm font-bold cursor-pointer transition-all ${variant === v ? "border-[#FFCD05] bg-[#FFCD05]/10 text-[#FFCD05]" : "border-white/15 text-white/60 hover:border-white/35"}`}>
                    {v}
                  </button>
                ))}
              </div>
            </div>
            <div className="mt-6">
              <div className="text-xs font-bold text-white/50 uppercase tracking-wider mb-3">Quantity</div>
              <div className="flex items-center gap-3">
                <button onClick={() => setQty(q => Math.max(1, q - 1))} className="w-10 h-10 rounded-xl border border-white/15 text-white font-bold hover:bg-white/8 cursor-pointer transition-colors">−</button>
                <span className="w-10 text-center font-black text-white text-lg">{qty}</span>
                <button onClick={() => setQty(q => q + 1)} className="w-10 h-10 rounded-xl border border-white/15 text-white font-bold hover:bg-white/8 cursor-pointer transition-colors">+</button>
                <span className="text-xs text-white/30">{p.stock} in stock</span>
              </div>
            </div>
            <div className="mt-8 flex gap-3">
              <Btn variant="primary" className="flex-1" onClick={() => { addToCart(); navigate("cart"); }}><ShoppingCart size={16} /> Add to Cart</Btn>
              <button className="w-12 h-12 rounded-xl border border-white/15 text-white/40 hover:text-[#FFCD05] hover:border-[#FFCD05]/40 cursor-pointer transition-all flex items-center justify-center"><Heart size={18} /></button>
            </div>
            <div className="mt-6 p-4 bg-[#141B2E] rounded-xl border border-white/8 flex items-center gap-3 text-sm text-white/40">
              <Shield size={15} className="text-emerald-400 shrink-0" />
              Secure checkout · M-Pesa, Visa & Mastercard · <span className="text-white font-semibold">7-day returns</span>
            </div>
          </div>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── CART ─────────────────────────────────────────────────────────────────────
function CartPage({ navigate, onCartChange }: { navigate: (p: Page) => void; onCartChange?: () => void }) {
  const [items, setItems] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const load = async () => {
    try {
      const cart = await api.fetchCart();
      const enriched = await Promise.all(cart.map(async (ci: any) => {
        try {
          const res = await fetch(`http://localhost:8091/api/data/products/${ci.productId}`, { headers: getAccessToken() ? { Authorization: `Bearer ${getAccessToken()}` } : {} });
          if (!res.ok) return null;
          const p = await res.json();
          return { ...ci, name: p.name, price: p.price, countyId: p.countyId, img: null, qty: ci.quantity };
        } catch { return null; }
      }));
      setItems(enriched.filter(Boolean));
    } catch { /* no cart */ }
    setLoading(false);
  };
  useEffect(() => { load() }, []);
  const sub = items.reduce((s, i) => s + (i.price || 0) * (i.qty || 1), 0);
  const vat = Math.round(sub * 0.16);
  const delivery = 350;
  return (
    <PageWrap>
      <div className="pt-20 max-w-5xl mx-auto px-5 py-10">
        <h1 className="text-2xl font-black text-white mb-8">Your Cart <span className="text-white/30 text-lg">({items.length})</span></h1>
        {loading && <p className="text-white/40 text-center py-10">Loading cart...</p>}
        {!loading && items.length === 0 && <p className="text-white/40 text-center py-10">Your cart is empty. <button onClick={() => navigate("marketplace")} className="text-[#FFCD05] underline">Browse marketplace</button></p>}
        <div className="grid lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-3">
            <AnimatePresence>
              {items.map((item, i) => (
                <motion.div key={item.id} initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 20, height: 0 }} transition={{ duration: 0.3 }}
                  className="bg-[#0D1220] rounded-2xl border border-white/8 p-4 flex gap-4 items-center">
                  <div className="w-20 h-20 rounded-xl overflow-hidden bg-[#141B2E] shrink-0 flex items-center justify-center"><span className="text-3xl">📦</span></div>
                  <div className="flex-1 min-w-0">
                    <div className="text-[10px] font-bold text-[#FFCD05] uppercase tracking-wider">#{item.productId}</div>
                    <div className="font-bold text-white text-sm line-clamp-1">{item.name || `Product #${item.productId}`}</div>
                    <div className="font-black text-[#FFCD05] mt-1">KES {fmt(item.price || 0)}</div>
                  </div>
                  <div className="flex items-center gap-2 shrink-0">
                    <button onClick={async () => {
                      const newQty = Math.max(1, (item.qty||1) - 1);
                      setItems(p => p.map((it, j) => j === i ? { ...it, qty: newQty } : it));
                      try { await api.updateCartItem(item.id, newQty); onCartChange?.() } catch {}
                    }} className="w-8 h-8 rounded-lg border border-white/15 text-white text-sm font-bold cursor-pointer hover:bg-white/8 flex items-center justify-center">−</button>
                    <span className="w-5 text-center text-sm font-bold text-white">{item.qty}</span>
                    <button onClick={async () => {
                      setItems(p => p.map((it, j) => j === i ? { ...it, qty: (item.qty||1) + 1 } : it));
                      try { await api.updateCartItem(item.id, (item.qty||1) + 1); onCartChange?.() } catch {}
                    }} className="w-8 h-8 rounded-lg border border-white/15 text-white text-sm font-bold cursor-pointer hover:bg-white/8 flex items-center justify-center">+</button>
                  </div>
                  <button onClick={async () => {
                    setItems(p => p.filter((_, j) => j !== i));
                    try { await api.removeFromCart(item.id); onCartChange?.() } catch {}
                  }} className="text-white/25 hover:text-[#901C1E] cursor-pointer shrink-0"><Trash2 size={15} /></button>
                </motion.div>
              ))}
            </AnimatePresence>
          </div>
          <div className="bg-[#0D1220] rounded-2xl border border-white/10 p-6 h-fit sticky top-24">
            <h3 className="font-black text-white text-lg mb-5">Summary</h3>
            <div className="space-y-3 text-sm">
              {[["Subtotal", `KES ${fmt(sub)}`], ["Delivery", `KES ${fmt(delivery)}`], ["VAT (16%)", `KES ${fmt(vat)}`]].map(([l, v]) => (
                <div key={l} className="flex justify-between text-white/50"><span>{l}</span><span className="text-white font-semibold">{v}</span></div>
              ))}
            </div>
            <div className="border-t border-white/8 mt-4 pt-4 flex justify-between font-black text-white text-lg">
              <span>Total</span><span className="text-[#FFCD05]">KES {fmt(sub + delivery + vat)}</span>
            </div>
            <Btn variant="primary" className="w-full mt-5" onClick={() => navigate("checkout")}>Checkout <ArrowRight size={16} /></Btn>
            <Btn variant="ghost-light" className="w-full mt-2" onClick={() => navigate("marketplace")}>Continue Shopping</Btn>
          </div>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── CHECKOUT ─────────────────────────────────────────────────────────────────
function CheckoutPage({ navigate }: { navigate: (p: Page) => void }) {
  const [method, setMethod] = useState("mpesa");
  return (
    <PageWrap>
      <div className="pt-20 max-w-5xl mx-auto px-5 py-10">
        <h1 className="text-2xl font-black text-white mb-8">Checkout</h1>
        <div className="grid lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-5">
            <div className="bg-[#0D1220] rounded-2xl border border-white/10 p-6">
              <h3 className="font-bold text-white mb-5">Delivery Details</h3>
              <div className="grid grid-cols-2 gap-4">
                {[["First Name","John"],["Last Name","Kamau"],["Phone","+254 712 345 678"],["Email","john@email.com"],["County","Nairobi"],["Town","Karen"]].map(([l, v]) => (
                  <div key={l}><label className="text-[10px] font-bold text-white/35 uppercase tracking-wider block mb-1.5">{l}</label>
                    <input defaultValue={v} className="w-full px-4 h-11 rounded-xl bg-[#141B2E] border border-white/10 text-white/80 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05]" /></div>
                ))}
              </div>
            </div>
            <div className="bg-[#0D1220] rounded-2xl border border-white/10 p-6">
              <h3 className="font-bold text-white mb-5">Payment Method</h3>
              {[["mpesa","M-Pesa","Lipa na M-Pesa STK Push"],["card","Debit / Credit Card","Visa · Mastercard · Amex"]].map(([id, label, sub]) => (
                <div key={id} onClick={() => setMethod(id)}
                  className={`flex items-center gap-4 p-4 rounded-xl border-2 mb-3 cursor-pointer transition-all ${method === id ? "border-[#FFCD05] bg-[#FFCD05]/5" : "border-white/10 hover:border-white/25"}`}>
                  <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 ${method === id ? "border-[#FFCD05]" : "border-white/25"}`}>
                    {method === id && <div className="w-2.5 h-2.5 bg-[#FFCD05] rounded-full" />}
                  </div>
                  <div><div className="font-bold text-white text-sm">{label}</div><div className="text-xs text-white/35">{sub}</div></div>
                </div>
              ))}
            </div>
          </div>
          <div className="bg-[#0D1220] rounded-2xl border border-white/10 p-6 h-fit sticky top-24">
            <h3 className="font-bold text-white mb-4">Order Summary</h3>
            {PRODUCTS.slice(0, 2).map(p => (
              <div key={p.id} className="flex gap-3 mb-3">
                <div className="w-12 h-12 rounded-xl overflow-hidden bg-[#141B2E] shrink-0"><img src={u(p.img, 96, 96)} alt="" className="w-full h-full object-cover" /></div>
                <div className="flex-1 min-w-0"><div className="text-xs font-semibold text-white line-clamp-1">{p.name}</div><div className="text-xs text-white/30">×2</div></div>
                <div className="text-sm font-bold text-[#FFCD05]">KES {fmt(p.price * 2)}</div>
              </div>
            ))}
            <div className="border-t border-white/8 pt-4 mt-4 flex justify-between font-black text-white text-lg"><span>Total</span><span className="text-[#FFCD05]">KES 9,350</span></div>
            <Btn variant="gold" className="w-full mt-5" onClick={() => navigate("mpesa")}>Pay KES 9,350 <ArrowRight size={16} /></Btn>
          </div>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── MPESA ────────────────────────────────────────────────────────────────────
function MpesaPage({ navigate }: { navigate: (p: Page) => void }) {
  const [state, setState] = useState<"input"|"processing"|"confirmed"|"failed">("input");
  const [phone, setPhone] = useState("0712 345 678");
  return (
    <PageWrap>
      <div className="pt-20 min-h-[80vh] flex items-center justify-center px-5">
        <div className="bg-[#0D1220] border border-white/10 rounded-3xl p-8 w-full max-w-sm text-center shadow-2xl shadow-black/60">
          {state === "input" && <>
            <div className="w-16 h-16 bg-[#00a651]/15 border border-[#00a651]/25 rounded-2xl flex items-center justify-center mx-auto mb-5"><Phone size={26} className="text-[#00a651]" /></div>
            <h2 className="text-2xl font-black text-white">Lipa na M-Pesa</h2>
            <p className="text-white/40 text-sm mt-2 mb-6">Enter your Safaricom number to receive an STK push</p>
            <div className="relative mb-4">
              <span className="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-white/40">+254</span>
              <input value={phone} onChange={e => setPhone(e.target.value)} className="w-full pl-14 pr-4 h-12 rounded-xl bg-[#141B2E] border border-white/10 text-white font-bold text-sm outline-none focus:ring-1 focus:ring-[#FFCD05]" />
            </div>
            <div className="bg-[#141B2E] rounded-xl p-4 mb-6 text-left border border-white/8">
              <div className="flex justify-between text-sm mb-1"><span className="text-white/40">Amount</span><span className="font-black text-[#FFCD05]">KES 9,350</span></div>
              <div className="flex justify-between text-sm"><span className="text-white/40">Paybill</span><span className="font-mono font-bold text-white">247247</span></div>
            </div>
            <Btn variant="gold" className="w-full" onClick={() => { setState("processing"); setTimeout(() => setState("confirmed"), 3200); }}>Send STK Push</Btn>
          </>}
          {state === "processing" && <>
            <div className="my-10 flex flex-col items-center gap-5">
              <motion.div animate={{ rotate: 360 }} transition={{ repeat: Infinity, duration: 1, ease: "linear" }} className="w-14 h-14 border-4 border-[#FFCD05]/20 border-t-[#FFCD05] rounded-full" />
              <div className="font-bold text-white text-lg">Check your phone</div>
              <p className="text-sm text-white/40">Enter your M-Pesa PIN to complete payment</p>
            </div>
            <button onClick={() => setState("failed")} className="text-xs text-white/30 underline cursor-pointer hover:text-white/50">Cancel</button>
          </>}
          {state === "confirmed" && <>
            <div className="my-8 flex flex-col items-center gap-4">
              <motion.div initial={{ scale: 0 }} animate={{ scale: 1 }} transition={{ type: "spring", stiffness: 300, damping: 18 }}
                className="w-16 h-16 bg-emerald-500/15 border border-emerald-500/30 rounded-full flex items-center justify-center">
                <CheckCircle size={32} className="text-emerald-400" />
              </motion.div>
              <div className="font-black text-white text-2xl">Confirmed!</div>
              <div className="font-mono text-sm text-white/40 bg-[#141B2E] px-4 py-2 rounded-lg border border-white/8">QAB7X9KM21</div>
              <p className="text-sm text-white/40">KES 9,350 paid successfully</p>
            </div>
            <Btn variant="primary" className="w-full" onClick={() => navigate("order-success")}>View Order <ArrowRight size={16} /></Btn>
          </>}
          {state === "failed" && <>
            <div className="my-8 flex flex-col items-center gap-4">
              <div className="w-16 h-16 bg-[#901C1E]/15 border border-[#901C1E]/30 rounded-full flex items-center justify-center"><XCircle size={32} className="text-[#f87171]" /></div>
              <div className="font-black text-white text-xl">Payment Failed</div>
              <p className="text-sm text-white/40">Transaction cancelled or timed out</p>
            </div>
            <Btn variant="primary" className="w-full" onClick={() => setState("input")}>Try Again</Btn>
          </>}
        </div>
      </div>
    </PageWrap>
  );
}

// ─── ORDER SUCCESS ────────────────────────────────────────────────────────────
function OrderSuccess({ navigate }: { navigate: (p: Page) => void }) {
  return (
    <PageWrap>
      <div className="pt-20 max-w-2xl mx-auto px-5 py-16 text-center">
        <motion.div initial={{ scale: 0 }} animate={{ scale: 1 }} transition={{ type: "spring", stiffness: 260, damping: 20 }}
          className="w-24 h-24 bg-emerald-500/15 border border-emerald-500/30 rounded-full flex items-center justify-center mx-auto mb-6">
          <CheckCircle size={44} className="text-emerald-400" />
        </motion.div>
        <h1 className="text-4xl font-black text-white">Order Placed!</h1>
        <p className="text-white/40 mt-3 mb-8">Order <span className="text-[#FFCD05] font-mono">KDP-2025-08812</span> confirmed. You'll receive an SMS update.</p>
        <div className="bg-[#0D1220] rounded-2xl border border-white/10 p-6 text-left mb-8">
          <h3 className="font-bold text-white mb-5">Delivery Timeline</h3>
          {[["Payment Confirmed","Right now","done"],["Seller Processing","~2 hours","done"],["Picked Up by Courier","Tomorrow","pending"],["Delivered","2–3 business days","pending"]].map(([s,t,st]) => (
            <div key={s as string} className="flex gap-4 mb-5 last:mb-0">
              <div className={`w-8 h-8 rounded-full flex items-center justify-center shrink-0 ${st === "done" ? "bg-emerald-500/20 border border-emerald-500/40" : "bg-white/5 border border-white/10"}`}>
                {st === "done" ? <CheckCircle size={15} className="text-emerald-400" /> : <Clock size={14} className="text-white/25" />}
              </div>
              <div className="pt-1"><div className="font-semibold text-white text-sm">{s as string}</div><div className="text-xs text-white/35 mt-0.5">{t as string}</div></div>
            </div>
          ))}
        </div>
        <div className="flex gap-3 justify-center">
          <Btn variant="primary" onClick={() => navigate("dash-buyer")}>Track Order</Btn>
          <Btn variant="dark" onClick={() => navigate("marketplace")}>Continue Shopping</Btn>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── EXHIBITION DETAIL ────────────────────────────────────────────────────────
function ExhibitionDetail({ navigate }: { navigate: (p: Page) => void }) {
  const ex = EXHIBITIONS[0];
  return (
    <PageWrap>
      <div className="pt-20">
        <div className="relative h-72 overflow-hidden">
          <img src={u(ex.img, 1400, 600)} alt={ex.name} className="w-full h-full object-cover opacity-60" />
          <div className="absolute inset-0 bg-gradient-to-t from-[#07090F] to-transparent" />
          <div className="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-8">
            <button onClick={() => navigate("home")} className="flex items-center gap-1.5 text-white/40 hover:text-white text-sm mb-3 cursor-pointer"><ChevronLeft size={15} /> Exhibitions</button>
            <Pill color="gold">Booking Open</Pill>
            <h1 className="text-3xl font-black text-white mt-3">{ex.name}</h1>
          </div>
        </div>
        <div className="max-w-7xl mx-auto px-5 py-10">
          <div className="grid lg:grid-cols-3 gap-8">
            <div className="lg:col-span-2">
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                {[[Calendar,"Dates",ex.dates],[MapPin,"Venue",ex.venue],[Package,"Booths",`${ex.booths} total`],[Users,"Visitors","18,000+"]].map(([Icon,l,v]) => (
                  <div key={l as string} className="bg-[#0D1220] border border-white/8 rounded-xl p-4">
                    {/* @ts-ignore */}
                    <Icon size={16} className="text-[#FFCD05] mb-2" />
                    <div className="text-[10px] font-bold text-white/35 uppercase tracking-wider">{l as string}</div>
                    <div className="font-bold text-white text-sm mt-1">{v as string}</div>
                  </div>
                ))}
              </div>
              <h2 className="text-xl font-black text-white mb-4">About this Exhibition</h2>
              <p className="text-white/45 text-sm leading-relaxed">Kenya's flagship annual trade fair connects exhibitors from all 47 counties with international buyers. This year's theme: "Digital Economy: Connecting Counties to the World." Feature your products and innovations to 18,000+ verified delegates.</p>
              <div className="mt-6">
                <h3 className="font-bold text-white mb-4">Event Programme</h3>
                {[["Opening Ceremony","2 Oct · 9:00 AM","Main Stage"],["County Showcase Days","3–5 Oct","All Halls"],["B2B Investment Forum","6–7 Oct","Tsavo Hall"],["Youth & SME Day","8 Oct","Aberdares Hall"],["Awards Gala Night","12 Oct · 7:00 PM","Amphitheatre"]].map(([s,d,v]) => (
                  <div key={s as string} className="flex items-center gap-4 py-3 border-b border-white/5 last:border-0">
                    <div className="w-2 h-2 bg-[#FFCD05] rounded-full shrink-0" />
                    <div className="flex-1"><div className="font-semibold text-white text-sm">{s as string}</div><div className="text-xs text-white/35">{v as string}</div></div>
                    <div className="text-xs font-semibold text-white/40">{d as string}</div>
                  </div>
                ))}
              </div>
            </div>
            <div>
              <div className="bg-[#0D1220] border border-white/10 rounded-2xl p-6 sticky top-24">
                <div className="font-black text-[#FFCD05] text-2xl">{ex.price}<span className="text-sm font-normal text-white/35">/booth</span></div>
                <div className="mt-5 space-y-3">
                  <Btn variant="primary" className="w-full" onClick={() => navigate("booth-picker")}>Choose a Booth <ArrowRight size={16} /></Btn>
                  <Btn variant="dark" className="w-full">Download Prospectus</Btn>
                </div>
                <div className="mt-6 pt-5 border-t border-white/8">
                  <div className="text-[10px] font-bold text-white/35 uppercase tracking-wider mb-3">Booth Packages</div>
                  {[["Standard","3×3m shell scheme","85,000"],["Premium","6×6m custom build","180,000"],["Pavilion","12×18m exclusive","450,000"]].map(([n,d,p]) => (
                    <div key={n as string} className="mb-3 p-3 bg-[#141B2E] border border-white/8 rounded-xl">
                      <div className="flex justify-between"><span className="font-bold text-white text-sm">{n as string}</span><span className="font-black text-[#FFCD05] text-sm">KES {p as string}</span></div>
                      <div className="text-xs text-white/30 mt-0.5">{d as string}</div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── BOOTH PICKER ─────────────────────────────────────────────────────────────
function BoothPicker({ navigate }: { navigate: (p: Page) => void }) {
  const [selected, setSelected] = useState<string | null>(null);
  const booths = Array.from({ length: 30 }, (_, i) => ({
    id: `B${String(i + 1).padStart(2, "0")}`, state: i < 9 ? "sold" : i < 15 ? "held" : "free", large: i % 7 === 0,
  }));
  return (
    <PageWrap>
      <div className="pt-20 max-w-6xl mx-auto px-5 py-10">
        <button onClick={() => navigate("exhibition")} className="flex items-center gap-1.5 text-white/40 hover:text-white text-sm mb-6 cursor-pointer"><ChevronLeft size={15} /> Kenya International Trade Fair</button>
        <SectionHead eyebrow="Hall A — Floor Plan" title={<>Choose Your <span className="text-[#FFCD05]">Booth</span></>} sub="Green = available · Yellow = on hold · Red = sold" />
        <div className="bg-[#0D1220] border border-white/10 rounded-2xl p-6 overflow-x-auto mb-6">
          <div className="bg-[#141B2E] rounded-xl py-3 px-6 text-center text-xs font-bold text-white/30 tracking-widest uppercase mb-6 border border-white/5">Main Entrance ↑</div>
          <div className="min-w-[560px] grid grid-cols-6 gap-2.5">
            {booths.map(b => {
              const sel = selected === b.id;
              const stateStyle = { free: "bg-emerald-500/15 border-emerald-500/40 text-emerald-300 hover:bg-emerald-500/25 cursor-pointer", held: "bg-[#FFCD05]/10 border-[#FFCD05]/30 text-[#FFCD05]/60 cursor-not-allowed", sold: "bg-[#901C1E]/15 border-[#901C1E]/30 text-[#901C1E]/50 cursor-not-allowed" }[b.state];
              return (
                <motion.button key={b.id} whileHover={b.state === "free" ? { scale: 1.05 } : {}} whileTap={b.state === "free" ? { scale: 0.95 } : {}}
                  onClick={() => b.state === "free" && setSelected(b.id)} disabled={b.state !== "free"}
                  className={`${b.large ? "col-span-2" : ""} border-2 rounded-xl p-3 text-xs font-bold transition-all ${sel ? "bg-[#0B1E57] border-[#FFCD05] text-white shadow-lg shadow-[#FFCD05]/20 scale-105" : stateStyle}`}>
                  {b.id}<div className="text-[9px] font-normal mt-0.5 opacity-60">{b.large ? "6×6m" : "3×3m"}</div>
                </motion.button>
              );
            })}
          </div>
        </div>
        <AnimatePresence>
          {selected && (
            <motion.div initial={{ opacity: 0, y: 16 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: 16 }}
              className="bg-[#0B1E57] border border-blue-500/30 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
              <div><div className="text-white font-black text-xl">Booth {selected} Selected</div><div className="text-blue-300 text-sm mt-1">Standard · 3×3m · Hall A</div></div>
              <Btn variant="gold" onClick={() => navigate("booth-checkout")}>Proceed to Payment — KES 85,000 <ArrowRight size={16} /></Btn>
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    </PageWrap>
  );
}

// ─── BOOTH CHECKOUT ───────────────────────────────────────────────────────────
function BoothCheckout({ navigate }: { navigate: (p: Page) => void }) {
  const [paying, setPaying] = useState(false);
  return (
    <PageWrap>
      <div className="pt-20 max-w-2xl mx-auto px-5 py-10">
        <button onClick={() => navigate("booth-picker")} className="flex items-center gap-1.5 text-white/40 hover:text-white text-sm mb-6 cursor-pointer"><ChevronLeft size={15} /> Booth Selection</button>
        <h1 className="text-2xl font-black text-white mb-8">Booth Booking Payment</h1>
        <div className="bg-[#0D1220] border border-white/10 rounded-2xl p-6 mb-6">
          <div className="bg-[#141B2E] rounded-xl border border-white/8 p-4 space-y-2 text-sm">
            {[["Booth fee (3×3m standard)","KES 85,000"],["Shell scheme & furniture","Included"],["Wi-Fi & utilities","Included"],["VAT (16%)","KES 13,600"]].map(([l,v]) => (
              <div key={l as string} className="flex justify-between text-white/50"><span>{l as string}</span><span className="font-semibold text-white">{v as string}</span></div>
            ))}
            <div className="flex justify-between font-black text-white text-base border-t border-white/8 pt-3 mt-2"><span>Total</span><span className="text-[#FFCD05]">KES 98,600</span></div>
          </div>
        </div>
        {!paying
          ? <Btn variant="gold" size="lg" className="w-full" onClick={() => { setPaying(true); setTimeout(() => navigate("booking-confirmed"), 2600); }}>Pay KES 98,600 via M-Pesa</Btn>
          : <div className="flex flex-col items-center gap-4 py-10">
              <motion.div animate={{ rotate: 360 }} transition={{ repeat: Infinity, duration: 1, ease: "linear" }} className="w-12 h-12 border-4 border-[#FFCD05]/20 border-t-[#FFCD05] rounded-full" />
              <div className="font-bold text-white">Processing payment…</div>
            </div>
        }
      </div>
    </PageWrap>
  );
}

// ─── BOOKING CONFIRMED ────────────────────────────────────────────────────────
function BookingConfirmed({ navigate }: { navigate: (p: Page) => void }) {
  return (
    <PageWrap>
      <div className="pt-20 max-w-lg mx-auto px-5 py-16 text-center">
        <motion.div initial={{ scale: 0, rotate: -15 }} animate={{ scale: 1, rotate: 0 }} transition={{ type: "spring", stiffness: 280, damping: 18 }}
          className="w-24 h-24 bg-[#FFCD05]/15 border border-[#FFCD05]/35 rounded-full flex items-center justify-center mx-auto mb-6">
          <QrCode size={40} className="text-[#FFCD05]" />
        </motion.div>
        <h1 className="text-3xl font-black text-white">Booth Booked!</h1>
        <p className="text-white/40 mt-3 mb-8">Booth B15 · Hall A · Kenya International Trade Fair 2025</p>
        <div className="bg-[#0D1220] border border-white/10 rounded-2xl p-6 mb-6">
          <div className="bg-[#141B2E] border border-white/8 rounded-xl p-8 flex items-center justify-center mb-4">
            <div className="grid grid-cols-10 gap-0.5">
              {Array.from({ length: 100 }, (_, i) => <div key={i} className={`w-3 h-3 ${((i * 7 + i % 11) % 3 === 0) ? "bg-white" : "bg-transparent"}`} />)}
            </div>
          </div>
          <div className="font-mono text-sm text-white/40">KICC-EXH-2025-B15-QAB7X9</div>
        </div>
        <div className="flex gap-3 justify-center">
          <Btn variant="primary" onClick={() => navigate("dash-exhibitor")}><LayoutDashboard size={16} /> My Dashboard</Btn>
          <Btn variant="dark"><Printer size={16} /> Print Ticket</Btn>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── VENUE PAGE ───────────────────────────────────────────────────────────────
function VenuePage({ navigate }: { navigate: (p: Page) => void }) {
  const [activeVenue, setActiveVenue] = useState((VENUES as any[])[0]);
  const [selDay, setSelDay] = useState<number | null>(null);
  const booked = [3,4,5,12,13,18,19,25];
  return (
    <PageWrap>
      <div className="pt-20">
        <div className="bg-[#0D1220] border-b border-white/8 py-6">
          <div className="max-w-7xl mx-auto px-5 flex gap-2 overflow-x-auto">
            {(VENUES as any[]).map((v: any) => (
              <button key={v.id} onClick={() => setActiveVenue(v)}
                className={`shrink-0 px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition-all ${activeVenue.id === v.id ? "bg-[#901C1E] text-white" : "text-white/50 hover:text-white hover:bg-white/8"}`}>
                {v.name}
              </button>
            ))}
          </div>
        </div>
        <div className="max-w-7xl mx-auto px-5 py-10">
          <div className="grid lg:grid-cols-3 gap-8">
            <div className="lg:col-span-2">
              <div className="rounded-2xl overflow-hidden h-72 bg-[#141B2E] mb-4">
                <motion.img key={activeVenue.id} src={u(activeVenue.fallback || activeVenue.img, 900, 500)} alt={activeVenue.name}
                  initial={{ opacity: 0, scale: 1.04 }} animate={{ opacity: 1, scale: 1 }} transition={{ duration: 0.5 }} className="w-full h-full object-cover" />
              </div>
              <h1 className="text-3xl font-black text-white">{activeVenue.name}</h1>
              <div className="flex flex-wrap gap-4 mt-3 text-sm text-white/45">
                <span className="flex items-center gap-1.5"><Users size={14} /> {activeVenue.capacity} max capacity</span>
                <span className="flex items-center gap-1.5"><Layers size={14} /> {activeVenue.area || (activeVenue.sqm ? `${activeVenue.sqm}m²` : "")} floor area</span>
                <span className="flex items-center gap-1.5"><MapPin size={14} /> City Square, Nairobi CBD</span>
              </div>
              <p className="mt-5 text-white/45 text-sm leading-relaxed">{activeVenue.desc}</p>
              <div className="mt-8">
                <h3 className="font-bold text-white mb-4">Availability — October 2025</h3>
                <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-5">
                  <div className="grid grid-cols-7 gap-1 mb-2">
                    {["M","T","W","T","F","S","S"].map((d, i) => <div key={i} className="text-center text-[10px] font-bold text-white/25 py-1">{d}</div>)}
                  </div>
                  <div className="grid grid-cols-7 gap-1">
                    {Array.from({ length: 31 }, (_, i) => i + 1).map(d => {
                      const bkd = booked.includes(d); const sel = selDay === d;
                      return (
                        <button key={d} onClick={() => !bkd && setSelDay(d)} disabled={bkd}
                          className={`aspect-square rounded-lg text-xs font-semibold flex items-center justify-center transition-all cursor-pointer ${sel ? "bg-[#FFCD05] text-[#07090F]" : bkd ? "bg-[#901C1E]/10 text-[#901C1E]/30 cursor-not-allowed" : "text-white/60 hover:bg-white/10 hover:text-white"}`}>
                          {d}
                        </button>
                      );
                    })}
                  </div>
                </div>
              </div>
            </div>
            <div>
              <div className="bg-[#0D1220] border border-white/10 rounded-2xl p-6 sticky top-24">
                <div className="font-black text-[#FFCD05] text-xl">{activeVenue.priceDay || activeVenue.price}<span className="text-sm font-normal text-white/35">/day</span></div>
                <div className="text-white/35 text-xs mt-1">+ 16% VAT · setup crew included</div>
                <Btn variant="primary" className="w-full mt-5">Request Booking</Btn>
                <p className="text-[10px] text-white/25 text-center mt-3">Response within 4 business hours · (+254) 20 3261000</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── LIVE PAGE ────────────────────────────────────────────────────────────────
function LivePage({ navigate }: { navigate: (p: Page) => void }) {
  const [billing, setBilling] = useState<"event" | "monthly">("event");
  return (
    <PageWrap>
      <div className="pt-20">
        <div className="relative overflow-hidden min-h-[420px] flex items-center">
          <img src={u("1741991109902-98bf764fb35d", 1400, 700)} alt="" className="absolute inset-0 w-full h-full object-cover opacity-25" />
          <div className="absolute inset-0 bg-gradient-to-b from-[#07090F]/80 via-[#07090F]/60 to-[#07090F]" />
          <div className="relative max-w-7xl mx-auto px-5 py-20 w-full">
            <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5 }}>
              <div className="flex items-center gap-3 mb-5">
                <motion.div animate={{ scale: [1, 1.25, 1] }} transition={{ repeat: Infinity, duration: 1.6 }} className="w-3 h-3 bg-[#f87171] rounded-full shadow-lg shadow-red-500/50" />
                <Pill color="red">Live Streaming — Now Available</Pill>
              </div>
              <h1 className="text-5xl md:text-7xl font-black text-white leading-[1.0] mb-5">Stream Your<br /><span className="text-[#FFCD05]">Exhibition</span><br />Worldwide</h1>
              <p className="text-white/50 text-lg max-w-xl leading-relaxed mb-8">Broadcast your KICC event live to viewers anywhere in Kenya and beyond. From 500 to unlimited concurrent viewers.</p>
              <div className="flex flex-wrap gap-3">
                <Btn variant="primary" size="lg"><Play size={18} /> Start Broadcasting</Btn>
                <Btn variant="outline-light" size="lg">Watch Live Events</Btn>
              </div>
            </motion.div>
          </div>
        </div>

        <div className="border-y border-white/8 bg-[#0D1220] py-8">
          <div className="max-w-7xl mx-auto px-5">
            <div className="flex items-center gap-3 mb-5">
              <motion.div animate={{ opacity: [1, 0.3, 1] }} transition={{ repeat: Infinity, duration: 1.4 }} className="w-2.5 h-2.5 bg-[#f87171] rounded-full" />
              <span className="text-white font-bold text-sm uppercase tracking-widest">Streaming Now</span>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              {LIVE_NOW.map((ev, i) => (
                <Reveal key={ev.id} delay={i * 0.07}>
                  <TiltCard>
                    <motion.div whileHover={{ y: -4 }} transition={{ duration: 0.2 }}
                      className="group relative rounded-2xl overflow-hidden cursor-pointer border border-white/8 hover:border-[#901C1E]/50 transition-all" style={{ height: 180 }}>
                      <img src={u(ev.img, 400, 280)} alt={ev.title} className="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                      <div className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent" />
                      <div className="absolute top-3 left-3">
                        {ev.live ? (
                          <span className="flex items-center gap-1.5 bg-[#f87171]/90 backdrop-blur text-white text-[10px] font-black px-2.5 py-1 rounded-full uppercase">
                            <motion.span animate={{ opacity: [1, 0.3, 1] }} transition={{ repeat: Infinity, duration: 1.2 }} className="w-1.5 h-1.5 bg-white rounded-full inline-block" /> Live
                          </span>
                        ) : (
                          <span className="bg-white/20 backdrop-blur text-white text-[10px] font-bold px-2.5 py-1 rounded-full">Replay</span>
                        )}
                      </div>
                      <div className="absolute bottom-0 left-0 right-0 p-4">
                        <div className="font-bold text-white text-sm leading-snug line-clamp-2">{ev.title}</div>
                        <div className="flex items-center justify-between mt-1.5">
                          <span className="text-white/45 text-xs">{ev.county}</span>
                          <span className="flex items-center gap-1 text-white/45 text-xs"><Eye size={11} />{ev.viewers.toLocaleString()}</span>
                        </div>
                      </div>
                      <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <div className="w-12 h-12 bg-white/15 backdrop-blur-sm border border-white/30 rounded-full flex items-center justify-center">
                          <Play size={18} className="text-white ml-0.5" />
                        </div>
                      </div>
                    </motion.div>
                  </TiltCard>
                </Reveal>
              ))}
            </div>
          </div>
        </div>

        <div className="bg-[#0D1220] border-y border-white/8 py-16">
          <div className="max-w-7xl mx-auto px-5">
            <Reveal>
              <div className="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
                <SectionHead eyebrow="Pricing" title={<>Event Holder <span className="text-[#FFCD05]">Pricing</span></>} sub="Pay per event or subscribe monthly — all plans include mobile viewer access." />
                <div className="flex items-center gap-1 bg-[#141B2E] border border-white/8 rounded-xl p-1 shrink-0 self-start">
                  {(["event", "monthly"] as const).map(b => (
                    <button key={b} onClick={() => setBilling(b)}
                      className={`px-5 py-2 rounded-lg text-xs font-bold cursor-pointer transition-all capitalize ${billing === b ? "bg-[#901C1E] text-white shadow-lg" : "text-white/40 hover:text-white"}`}>
                      {b === "event" ? "Per Event" : "Monthly"}
                    </button>
                  ))}
                </div>
              </div>
            </Reveal>
            <AnimatePresence mode="wait">
              {billing === "event" ? (
                <motion.div key="event" initial={{ opacity: 0, y: 16 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -10 }} transition={{ duration: 0.3 }} className="grid md:grid-cols-3 gap-5">
                  {LIVE_PLANS.map((plan, i) => (
                    <Reveal key={plan.id} delay={i * 0.1}>
                      <TiltCard>
                        <div className={`relative rounded-2xl border h-full flex flex-col transition-all overflow-hidden ${plan.highlight ? "border-[#901C1E] shadow-2xl shadow-[#901C1E]/20" : "border-white/10 hover:border-white/20"}`}>
                          {plan.highlight && <div className="bg-[#901C1E] text-white text-[10px] font-black uppercase tracking-widest text-center py-2 px-4">★ Most Popular</div>}
                          <div className="p-7 flex flex-col flex-1 bg-[#0D1220]">
                            <div className="w-10 h-10 rounded-xl flex items-center justify-center mb-5" style={{ background: plan.color + "22", border: `1px solid ${plan.color}44` }}>
                              <Play size={16} style={{ color: plan.color }} />
                            </div>
                            <div className="font-black text-white text-lg mb-1">{plan.name}</div>
                            <div className="text-white/40 text-xs mb-5 leading-relaxed">{plan.desc}</div>
                            <div className="mb-6"><span className="text-4xl font-black text-white">KES {fmt(plan.price)}</span><span className="text-white/35 text-sm ml-1">/{plan.period}</span></div>
                            <ul className="space-y-2.5 flex-1 mb-7">
                              {plan.features.map(f => (
                                <li key={f} className="flex items-start gap-2.5 text-sm">
                                  <CheckCircle size={14} className="shrink-0 mt-0.5" style={{ color: plan.color }} />
                                  <span className="text-white/60">{f}</span>
                                </li>
                              ))}
                            </ul>
                            <Btn variant={plan.highlight ? "primary" : "dark"} className="w-full" onClick={() => navigate("login")}>
                              {plan.highlight ? "Get Started" : "Choose Plan"} <ArrowRight size={15} />
                            </Btn>
                          </div>
                        </div>
                      </TiltCard>
                    </Reveal>
                  ))}
                </motion.div>
              ) : (
                <motion.div key="monthly" initial={{ opacity: 0, y: 16 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -10 }} transition={{ duration: 0.3 }} className="grid md:grid-cols-3 gap-5">
                  {LIVE_MONTHLY.map((plan, i) => (
                    <Reveal key={plan.name} delay={i * 0.1}>
                      <TiltCard>
                        <div className="bg-[#0D1220] border border-white/10 hover:border-white/20 rounded-2xl p-7 flex flex-col h-full transition-all">
                          <div className="w-10 h-10 rounded-xl flex items-center justify-center mb-5" style={{ background: plan.color + "22", border: `1px solid ${plan.color}44` }}>
                            <Wifi size={16} style={{ color: plan.color }} />
                          </div>
                          <div className="font-black text-white text-lg mb-1">{plan.name}</div>
                          <div className="text-4xl font-black text-white my-5">KES {fmt(plan.price)}<span className="text-sm font-normal text-white/35">/mo</span></div>
                          <div className="space-y-3 flex-1 mb-7">
                            {[["Events per month", String(plan.events)],["Concurrent viewers", plan.viewers],["Recording & replay","Included"],["Analytics dashboard","Included"]].map(([l, v]) => (
                              <div key={l} className="flex items-center justify-between text-sm border-b border-white/5 pb-2.5">
                                <span className="text-white/40">{l}</span><span className="text-white font-semibold">{v}</span>
                              </div>
                            ))}
                          </div>
                          <Btn variant="dark" className="w-full" onClick={() => navigate("login")}>Subscribe <ArrowRight size={15} /></Btn>
                        </div>
                      </TiltCard>
                    </Reveal>
                  ))}
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-5 py-16">
          <Reveal><SectionHead eyebrow="Features" title={<>Built for <span className="text-[#FFCD05]">Kenya's Events</span></>} /></Reveal>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            {[{icon: Wifi, title: "Multi-Camera", desc:"Up to 4 camera inputs synced seamlessly"},{icon: Globe, title: "Global CDN", desc:"Delivered fast to viewers across East Africa"},{icon: Phone, title: "Mobile-First", desc:"Optimised for Safaricom & Airtel data speeds"},{icon: Shield, title: "Secure Stream", desc:"Password protection and viewer verification"},{icon: BarChart3, title: "Live Analytics", desc:"Real-time viewer count and engagement"},{icon: MonitorPlay, title: "Simulcast", desc:"Broadcast to YouTube, Facebook & KICC at once"},{icon: Download, title: "Full Recording", desc:"Download MP4 after the event ends"},{icon: Users, title: "Live Q&A", desc:"Audience questions routed to your moderator"}].map((f, i) => (
              <Reveal key={f.title} delay={i * 0.05}>
                <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-5 hover:border-[#FFCD05]/30 transition-all">
                  <div className="w-9 h-9 bg-[#FFCD05]/10 border border-[#FFCD05]/20 rounded-xl flex items-center justify-center mb-3"><f.icon size={16} className="text-[#FFCD05]" /></div>
                  <div className="font-bold text-white text-sm mb-1">{f.title}</div>
                  <div className="text-white/35 text-xs leading-relaxed">{f.desc}</div>
                </div>
              </Reveal>
            ))}
          </div>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── SCREENS PAGE ─────────────────────────────────────────────────────────────
function ScreensPage({ navigate }: { navigate: (p: Page) => void }) {
  return (
    <PageWrap>
      <div className="pt-20">
        <div className="relative h-64 overflow-hidden">
          <img src={u("1741991109886-90e70988f27b", 1400, 600)} alt="KICC screens" className="w-full h-full object-cover opacity-50" />
          <div className="absolute inset-0 bg-gradient-to-t from-[#07090F]" />
          <div className="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-8">
            <Pill color="gold">18 Premium Screens</Pill>
            <h1 className="text-4xl font-black text-white mt-2">Digital Out-of-Home<br /><span className="text-[#FFCD05]">Advertising</span></h1>
          </div>
        </div>
        <div className="max-w-7xl mx-auto px-5 py-12">
          <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-12">
            {SCREENS.map((s, i) => (
              <Reveal key={s.id} delay={i * 0.08}>
                <TiltCard>
                  <div className="bg-[#0D1220] rounded-2xl overflow-hidden border border-white/8 hover:border-[#FFCD05]/40 transition-all">
                    <div className="h-40 overflow-hidden bg-[#141B2E]">
                      <img src={u(s.img, 400, 280)} alt={s.name} className="w-full h-full object-cover opacity-70" />
                    </div>
                    <div className="p-5">
                      <div className="font-black text-white text-sm">{s.name}</div>
                      <div className="text-white/40 text-xs mt-1">{s.location}</div>
                      <div className="flex items-center justify-between mt-4">
                        <div><div className="text-[#FFCD05] font-black text-base">{s.price}</div><div className="text-white/25 text-[10px]">{s.size} display</div></div>
                        <Btn variant="primary" size="sm" onClick={() => navigate("checkout")}>Book</Btn>
                      </div>
                    </div>
                  </div>
                </TiltCard>
              </Reveal>
            ))}
          </div>
          <div className="bg-[#0D1220] border border-white/10 rounded-2xl p-8 text-center">
            <MonitorPlay size={36} className="text-[#FFCD05] mx-auto mb-4" />
            <h2 className="text-2xl font-black text-white mb-2">Custom Campaign</h2>
            <p className="text-white/40 text-sm max-w-md mx-auto mb-6">Need multiple screens or a long-term contract? Our media team will build a bespoke package.</p>
            <Btn variant="gold" size="lg">Talk to Our Media Team</Btn>
          </div>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── HOTEL BOOKING ────────────────────────────────────────────────────────────
function HotelBooking({ navigate }: { navigate: (p: Page) => void }) {
  const [step, setStep] = useState(1);
  return (
    <PageWrap>
      <div className="pt-20 max-w-3xl mx-auto px-5 py-10">
        <button onClick={() => navigate("entity")} className="flex items-center gap-1.5 text-white/40 hover:text-white text-sm mb-6 cursor-pointer"><ChevronLeft size={15} /> Hemingways Nairobi</button>
        <h1 className="text-2xl font-black text-white mb-2">Book Your Stay</h1>
        <p className="text-white/40 text-sm mb-8">Hemingways Nairobi · Karen, Nairobi</p>
        <div className="flex items-center gap-2 mb-8">
          {["Dates & Rooms", "Guest Info", "Payment"].map((s, i) => (
            <div key={s} className="flex items-center gap-2 flex-1">
              <div className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-black shrink-0 transition-all ${step > i+1 ? "bg-emerald-500 text-white" : step === i+1 ? "bg-[#901C1E] text-white" : "bg-[#141B2E] border border-white/15 text-white/30"}`}>
                {step > i+1 ? <CheckCircle size={14} /> : i+1}
              </div>
              <div className="text-xs font-semibold text-white/40 hidden sm:block">{s}</div>
              {i < 2 && <div className={`flex-1 h-px ${step > i+1 ? "bg-emerald-500" : "bg-white/10"}`} />}
            </div>
          ))}
        </div>
        <div className="bg-[#0D1220] border border-white/10 rounded-2xl p-6">
          {step === 1 && (
            <div className="space-y-5">
              <div className="grid grid-cols-2 gap-4">
                {[["Check-in","2025-10-15"],["Check-out","2025-10-18"]].map(([l,v]) => (
                  <div key={l}><label className="text-[10px] font-bold text-white/35 uppercase tracking-wider block mb-1.5">{l}</label>
                    <input type="date" defaultValue={v} className="w-full px-4 h-11 rounded-xl bg-[#141B2E] border border-white/10 text-white/70 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05]" /></div>
                ))}
              </div>
              <div>
                <label className="text-[10px] font-bold text-white/35 uppercase tracking-wider block mb-2">Room Type</label>
                <div className="space-y-2">
                  {[["Superior Garden Suite","KES 28,000/night",true],["Deluxe Pool Suite","KES 42,000/night",false],["The Hemingways Suite","KES 95,000/night",false]].map(([n,p,sel]) => (
                    <div key={n as string} className={`flex items-center justify-between p-4 rounded-xl border-2 cursor-pointer transition-all ${sel ? "border-[#FFCD05] bg-[#FFCD05]/5" : "border-white/10 hover:border-white/25"}`}>
                      <div className="font-bold text-white text-sm">{n as string}</div><div className="font-black text-[#FFCD05] text-sm">{p as string}</div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}
          {step === 2 && (
            <div className="grid grid-cols-2 gap-4">
              {[["First Name","John"],["Last Name","Kamau"],["Email","john@email.com"],["Phone","+254 712 345 678"],["Nationality","Kenyan"],["ID / Passport","A12345678"]].map(([l,v]) => (
                <div key={l}><label className="text-[10px] font-bold text-white/35 uppercase tracking-wider block mb-1.5">{l}</label>
                  <input defaultValue={v} className="w-full px-4 h-11 rounded-xl bg-[#141B2E] border border-white/10 text-white/70 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05]" /></div>
              ))}
            </div>
          )}
          {step === 3 && (
            <div>
              <div className="bg-[#141B2E] border border-white/8 rounded-xl p-5 mb-6 space-y-2 text-sm">
                {[["Superior Garden Suite × 3 nights","KES 84,000"],["Service charge (10%)","KES 8,400"],["VAT (16%)","KES 14,784"]].map(([l,v]) => (
                  <div key={l as string} className="flex justify-between text-white/50"><span>{l as string}</span><span className="text-white font-semibold">{v as string}</span></div>
                ))}
                <div className="flex justify-between font-black text-white text-lg border-t border-white/8 pt-3 mt-1"><span>Total</span><span className="text-[#FFCD05]">KES 107,184</span></div>
              </div>
              <Btn variant="gold" size="lg" className="w-full" onClick={() => navigate("mpesa")}>Pay KES 107,184 via M-Pesa</Btn>
            </div>
          )}
          <div className="mt-6 flex justify-between">
            {step > 1 ? <Btn variant="ghost-light" onClick={() => setStep(s => s - 1)}><ChevronLeft size={15} /> Back</Btn> : <div />}
            {step < 3 && <Btn variant="primary" onClick={() => setStep(s => s + 1)}>Continue <ArrowRight size={15} /></Btn>}
          </div>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── AUTH ─────────────────────────────────────────────────────────────────────
function LoginPage({ navigate, onLogin }: { navigate: (p: Page) => void; onLogin: (email: string, password: string) => Promise<void> }) {
  const [email, setEmail] = useState("admin@kicc.go.ke");
  const [pass, setPass] = useState("Admin@2026");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  return (
    <PageWrap>
      <div className="min-h-screen flex items-center justify-center px-5">
        <motion.div initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5 }}
          className="bg-[#0D1220] border border-white/10 rounded-3xl p-8 w-full max-w-sm shadow-2xl shadow-black/60">
          <div className="text-center mb-8">
            <KICCLogo />
            <div className="mt-6 font-black text-2xl text-white">Welcome Back</div>
            <p className="text-white/40 text-sm mt-1">Sign in to your KICC account</p>
          </div>
          <div className="mb-4">
            <label className="text-[10px] font-bold text-white/35 uppercase tracking-wider block mb-1.5">Email</label>
            <input value={email} onChange={e => setEmail(e.target.value)} className="w-full px-4 h-12 rounded-xl bg-[#141B2E] border border-white/10 text-white font-bold text-sm outline-none focus:ring-1 focus:ring-[#FFCD05]" />
          </div>
          <div className="mb-4">
            <label className="text-[10px] font-bold text-white/35 uppercase tracking-wider block mb-1.5">Password</label>
            <input type="password" value={pass} onChange={e => setPass(e.target.value)} className="w-full px-4 h-12 rounded-xl bg-[#141B2E] border border-white/10 text-white font-bold text-sm outline-none focus:ring-1 focus:ring-[#FFCD05]" />
          </div>
          {error && <p className="text-red-400 text-xs mb-3">{error}</p>}
          <Btn variant="primary" size="lg" className="w-full" disabled={busy} onClick={async () => { setBusy(true); setError(""); try { await onLogin(email, pass) } catch (e: any) { setError(e.message || "Login failed") } finally { setBusy(false) }}}>
            {busy ? "Signing in…" : "Sign In"} <ArrowRight size={16} />
          </Btn>
          <div className="mt-8 pt-6 border-t border-white/8">
            <div className="text-[10px] font-bold text-white/30 text-center mb-3 uppercase tracking-widest">Quick Demo Access</div>
            <div className="grid grid-cols-2 gap-2">
              {(["admin@kicc.go.ke","national@kicc.go.ke","county@kicc.go.ke","exhibitor@kicc.go.ke"] as const).map(email => (
                <button key={email} onClick={() => { setEmail(email); setPass(email.includes("admin") ? "Admin@2026" : email.includes("national") ? "national@2026" : email.includes("county") ? "county@2026" : "exhibitor@2026") }}
                  className="py-2 px-3 bg-[#141B2E] border border-white/8 hover:border-[#FFCD05]/40 hover:text-[#FFCD05] rounded-xl text-xs font-bold text-white/50 cursor-pointer transition-all">
                  {email.split("@")[0]}
                </button>
              ))}
            </div>
          </div>
        </motion.div>
      </div>
    </PageWrap>
  );
}

function OtpPage({ navigate, onLogin }: { navigate: (p: Page) => void; onLogin: (r: "buyer") => void }) {
  const [otp, setOtp] = useState(["","","","","",""]);
  const refs = Array.from({ length: 6 }, () => useRef<HTMLInputElement>(null));
  const full = otp.every(v => v !== "");
  return (
    <PageWrap>
      <div className="min-h-screen flex items-center justify-center px-5">
        <div className="bg-[#0D1220] border border-white/10 rounded-3xl p-8 w-full max-w-sm text-center shadow-2xl shadow-black/60">
          <div className="w-14 h-14 bg-[#0B1E57]/30 border border-blue-500/30 rounded-2xl flex items-center justify-center mx-auto mb-5"><Phone size={24} className="text-blue-300" /></div>
          <h2 className="text-2xl font-black text-white">Enter OTP</h2>
          <p className="text-white/40 text-sm mt-2 mb-8">Sent to +254 712 *** 678</p>
          <div className="flex gap-2 justify-center mb-8">
            {otp.map((v, i) => (
              <input key={i} ref={refs[i]} value={v} maxLength={1}
                onChange={e => { const n = [...otp]; n[i] = e.target.value.slice(-1); setOtp(n); if (e.target.value && i < 5) refs[i+1].current?.focus(); }}
                className="w-11 h-14 rounded-xl border-2 border-white/10 bg-[#141B2E] text-center text-xl font-black text-white outline-none focus:border-[#FFCD05] transition-all" />
            ))}
          </div>
          <Btn variant="primary" className="w-full" disabled={!full} onClick={() => { onLogin("buyer"); navigate("dash-buyer"); }}>
            {full ? "Verify & Sign In" : "Enter code above"}
          </Btn>
          <button className="mt-4 text-sm text-white/25 underline cursor-pointer hover:text-white/50">Resend code</button>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── APP ROOT ─────────────────────────────────────────────────────────────────
export default function App() {
  const [page, setPage] = useState<Page>("home");
  const [cartCount, setCartCount] = useState(0);
  const [me, setMe] = useState<any>(null);
  const [realCounties, setRealCounties] = useState<any[] | null>(null);
  const [realProducts, setRealProducts] = useState<any[] | null>(null);
  const containerRef = useRef<HTMLDivElement>(null);
  const navigate = (p: Page) => { setPage(p); setTimeout(() => containerRef.current?.scrollTo({ top: 0 }), 50); };

  const refreshCart = useCallback(async () => {
    try { const c = await api.fetchCart(); setCartCount(c.length); } catch {}
  }, []);

  // Load public data on mount
  useEffect(() => {
    fetch('http://localhost:8091/api/data/public/counties')
      .then(r => r.json()).then(setRealCounties).catch(() => {})
    fetch('http://localhost:8091/api/marketplace/products')
      .then(r => r.json()).then(setRealProducts).catch(() => {})
    if (getAccessToken()) {
      api.fetchMe().then(m => { setMe(m); refreshCart() }).catch(() => {})
    }
  }, []);

  const onLogin = useCallback(async (email: string, password: string) => {
    try {
      const m = await login(email, password);
      setMe(m);
      refreshCart();
      const dashMap: Record<string, Page> = {
        KICC: 'dash-admin', NATIONAL: 'dash-county', COUNTY: 'dash-county', EXHIBITOR: 'dash-exhibitor'
      };
      setPage(dashMap[m.tier] || 'dash-buyer');
    } catch {}
  }, []);
  const onLogout = useCallback(async () => {
    await logout();
    setMe(null);
    setCartCount(0);
    setPage('home');
  }, []);
  const addToCart = useCallback(() => setCartCount(n => n + 1), []);

  // Use real data when available, fallback to mock
  const countiesData = realCounties || COUNTIES;
  const productsData = realProducts || PRODUCTS;

  const isDash = page.startsWith("dash-");
  const isAuth = page === "login" || page === "otp";
  const showNav = !isDash && !isAuth;
  return (
    <div ref={containerRef} className="min-h-screen bg-[#07090F] overflow-x-hidden" style={{ fontFamily: "'Montserrat', sans-serif" }}>
      <style>{`* { font-family: 'Montserrat', sans-serif; } ::-webkit-scrollbar { width: 5px; } ::-webkit-scrollbar-track { background: transparent; } ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; } ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); } .scale-108 { transform: scale(1.08); }`}</style>
      {showNav && <Nav navigate={navigate} cartCount={cartCount} />}
      <AnimatePresence mode="wait">
        <div key={page}>
          {page === "home"              && <HomePage navigate={navigate} />}
          {page === "county"            && <CountyPage navigate={navigate} />}
          {page === "sector"            && <SectorPage navigate={navigate} />}
          {page === "entity"            && <EntityDetail navigate={navigate} />}
          {page === "marketplace"       && <MarketplacePage navigate={navigate} addToCart={addToCart} />}
          {page === "product"           && <ProductDetail navigate={navigate} addToCart={addToCart} />}
          {page === "cart"              && <CartPage navigate={navigate} onCartChange={refreshCart} />}
          {page === "checkout"          && <CheckoutPage navigate={navigate} />}
          {page === "mpesa"             && <MpesaPage navigate={navigate} />}
          {page === "order-success"     && <OrderSuccess navigate={navigate} />}
          {page === "exhibition"        && <ExhibitionDetail navigate={navigate} />}
          {page === "booth-picker"      && <BoothPicker navigate={navigate} />}
          {page === "booth-checkout"    && <BoothCheckout navigate={navigate} />}
          {page === "booking-confirmed" && <BookingConfirmed navigate={navigate} />}
          {page === "venue"             && <VenuePage navigate={navigate} />}
          {page === "screens"           && <ScreensPage navigate={navigate} />}
          {page === "live"              && <LivePage navigate={navigate} />}
          {page === "hotel-booking"     && <HotelBooking navigate={navigate} />}
          {page === "login"             && <LoginPage navigate={navigate} onLogin={onLogin} />}
          {page === "otp"               && <OtpPage navigate={navigate} onLogin={onLogin as any} />}
          {page === "dash-buyer"        && <DashBuyer navigate={navigate} />}
          {page === "dash-exhibitor"    && <DashExhibitor navigate={navigate} />}
          {page === "dash-seller"       && <DashSeller navigate={navigate} />}
          {page === "dash-county"       && <DashCounty navigate={navigate} />}
          {page === "dash-admin"        && <DashAdmin navigate={navigate} me={me} onLogout={onLogout} />}
          {page === "directory"         && <DirectoryPage navigate={navigate} />}
          {page === "exhibitor-public"  && <ExhibitorPublicProfile navigate={navigate} />}
        </div>
      </AnimatePresence>
      {showNav && <Footer navigate={navigate} />}
    </div>
  );
}
