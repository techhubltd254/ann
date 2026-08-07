import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";
import { ChevronLeft } from "lucide-react";
import {
  type Page,
  PRODUCTS,
  u, fmt,
  MapPin, Phone, Mail, Globe, Shield, Star,
  Eye, CheckCircle, Package, Users, ExternalLink,
  ChevronRight, Sparkles, Tag,
  Pill, Btn, TiltCard, PageWrap, Reveal,
} from "./shared";

const DIRECTORY_EXHIBITORS = [
  { id: "d1",  name: "Savanna Crafts Kenya",    county: "Nairobi",  sector: "Handicrafts",       tagline: "Authentic handmade goods from Kenyan artisans",               verified: true,  products: 42, logo: "1783024865247-d775f6b0b41b", banner: "1776409933815-3497439f829a", plan: "pro" },
  { id: "d2",  name: "Mt Kenya Coffee Co.",     county: "Nyeri",    sector: "Agriculture",        tagline: "Single-origin Kenyan AA coffee grown at 1,800m altitude",     verified: true,  products: 12, logo: "1773858437375-e49a91b73ff1", banner: "1741991109886-90e70988f27b", plan: "pro" },
  { id: "d3",  name: "Coastal Naturals Ltd",    county: "Mombasa",  sector: "Health & Wellness",  tagline: "Cold-pressed coconut oils and natural body products",           verified: true,  products: 28, logo: "1564490292125-2e3c78a0ef44", banner: "1652511928669-f3ce2797913b", plan: "standard" },
  { id: "d4",  name: "Enkiama Crafts",          county: "Kajiado",  sector: "Handicrafts",        tagline: "Maasai beadwork, jewellery and traditional leatherwork",        verified: true,  products: 65, logo: "1772411535291-aa5884035934", banner: "1517503462743-f87ba95a8cae", plan: "pro" },
  { id: "d5",  name: "Lake Victoria Fisheries", county: "Kisumu",   sector: "Agri & Food",        tagline: "Fresh and processed Nile perch from Lake Victoria",             verified: false, products: 8,  logo: "1751568928684-9fa66911be90", banner: "1751568928581-874900ec53f0", plan: "standard" },
  { id: "d6",  name: "Savanna Safaris",         county: "Laikipia", sector: "Tourism",            tagline: "Premium eco-safari camps and wildlife experiences",             verified: true,  products: 15, logo: "1709402606682-400133d92ab2", banner: "1745526220488-6d7b179ac084", plan: "enterprise" },
  { id: "d7",  name: "Heritage Pottery",        county: "Kisii",    sector: "Handicrafts",        tagline: "Hand-thrown soapstone and clay pottery since 1982",             verified: true,  products: 34, logo: "1776409933876-022c6cb0baf6", banner: "1783024865247-d775f6b0b41b", plan: "standard" },
  { id: "d8",  name: "Lamu Heritage Tours",     county: "Lamu",     sector: "Tourism",            tagline: "UNESCO heritage dhow safaris and island experiences",           verified: true,  products: 7,  logo: "1652511931085-97666ab442ce", banner: "1664093671757-df1b2a7bb5da", plan: "pro" },
  { id: "d9",  name: "Rift Valley Dairy",       county: "Nakuru",   sector: "Agri & Food",        tagline: "Award-winning yoghurts, cheese and fresh milk products",        verified: false, products: 21, logo: "1554490594-0e5b97120099",    banner: "1706394212063-1017e86d5d4b", plan: "standard" },
  { id: "d10", name: "Nairobi Tech Hub",        county: "Nairobi",  sector: "Tech & Innovation",  tagline: "Software solutions and digital services for businesses",        verified: true,  products: 9,  logo: "1741991110666-88115e724741", banner: "1611144727915-ef30a08aaeb3", plan: "enterprise" },
  { id: "d11", name: "Maasai Naturals",         county: "Kajiado",  sector: "Health & Wellness",  tagline: "Organic shea butter and herbal wellness products",              verified: true,  products: 18, logo: "1602009786436-96b827675d32", banner: "1777065851469-71aef898a26f", plan: "standard" },
  { id: "d12", name: "Bamburi Textiles",        county: "Mombasa",  sector: "Fashion & Textiles", tagline: "Hand-printed kitenge and batik fabric since 1974",              verified: true,  products: 55, logo: "1772411534854-e00e174b596d", banner: "1779050527960-492cc801ad40", plan: "pro" },
  { id: "d13", name: "Ol Pejeta Conservancy",  county: "Laikipia", sector: "Tourism",            tagline: "Big 5 wildlife conservancy and rhino sanctuary experiences",    verified: true,  products: 5,  logo: "1745526220488-6d7b179ac084", banner: "1709402606682-400133d92ab2", plan: "enterprise" },
  { id: "d14", name: "Nakuru Honey House",      county: "Nakuru",   sector: "Agri & Food",        tagline: "Pure wildflower honey from the Rift Valley highlands",          verified: true,  products: 11, logo: "1729686689344-2f8bb3f65e5c", banner: "1554490594-0e5b97120099",    plan: "standard" },
  { id: "d15", name: "Diani Beach Eco-Lodge",  county: "Kwale",    sector: "Tourism",            tagline: "Sustainable beachfront eco-lodges on Kenya's most beautiful coast",verified: true,products: 4,  logo: "1664093671757-df1b2a7bb5da", banner: "1564490292125-2e3c78a0ef44", plan: "pro" },
];

const ALL_SECTORS_DIR = ["All Sectors","Agriculture","Handicrafts","Health & Wellness","Tourism","Agri & Food","Tech & Innovation","Fashion & Textiles"];

export function DirectoryPage({ navigate }: { navigate: (p: Page) => void }) {
  const [search, setSearch] = useState("");
  const [sector, setSector] = useState("All Sectors");
  const [county, setCounty] = useState("All Counties");
  const [plan, setPlan] = useState("all");
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [sortBy, setSortBy] = useState<"name" | "products" | "verified">("verified");

  const filtered = DIRECTORY_EXHIBITORS.filter(e => {
    const matchSearch = !search || e.name.toLowerCase().includes(search.toLowerCase()) || e.tagline.toLowerCase().includes(search.toLowerCase());
    const matchSector = sector === "All Sectors" || e.sector === sector;
    const matchCounty = county === "All Counties" || e.county === county;
    const matchPlan = plan === "all" || e.plan === plan;
    return matchSearch && matchSector && matchCounty && matchPlan;
  }).sort((a, b) => {
    if (sortBy === "verified") return (b.verified ? 1 : 0) - (a.verified ? 1 : 0);
    if (sortBy === "products") return b.products - a.products;
    return a.name.localeCompare(b.name);
  });

  const counties = ["All Counties", ...Array.from(new Set(DIRECTORY_EXHIBITORS.map(e => e.county)))];

  if (selectedId) {
    const exhibitor = DIRECTORY_EXHIBITORS.find(e => e.id === selectedId);
    if (exhibitor) return <ExhibitorPublicProfile navigate={navigate} exhibitor={exhibitor} onBack={() => setSelectedId(null)} />;
  }

  return (
    <PageWrap>
      {/* Hero */}
      <div className="relative py-20 px-4 text-center overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-b from-[#0B1E57]/30 via-transparent to-transparent pointer-events-none" />
        <Reveal>
          <div className="inline-flex items-center gap-2 bg-[#901C1E]/10 border border-[#901C1E]/25 rounded-full px-4 py-2 text-xs font-bold text-[#FFCD05] uppercase tracking-widest mb-5">
            <Sparkles size={12} /> Kenya Exhibitor Directory
          </div>
          <h1 className="text-4xl md:text-5xl font-black text-white mb-4 leading-tight">
            Browse Kenya's <span className="text-[#FFCD05]">Top Exhibitors</span>
          </h1>
          <p className="text-white/50 max-w-xl mx-auto text-lg mb-2">
            Discover verified businesses from all 47 counties — browse products, contact suppliers, and explore Kenya's marketplace.
          </p>
          <div className="flex items-center justify-center gap-6 mt-4 text-sm text-white/30">
            <span className="flex items-center gap-1.5"><CheckCircle size={13} className="text-emerald-400" />{DIRECTORY_EXHIBITORS.filter(e => e.verified).length} Verified Exhibitors</span>
            <span className="flex items-center gap-1.5"><Package size={13} className="text-[#FFCD05]" />{DIRECTORY_EXHIBITORS.reduce((s, e) => s + e.products, 0)} Products Listed</span>
            <span className="flex items-center gap-1.5"><MapPin size={13} className="text-blue-300" />{new Set(DIRECTORY_EXHIBITORS.map(e => e.county)).size} Counties</span>
          </div>
        </Reveal>
      </div>

      {/* Search + Filters */}
      <div className="sticky top-16 z-30 bg-[#07090F]/90 backdrop-blur-xl border-b border-white/6 py-4 px-4">
        <div className="max-w-6xl mx-auto">
          <div className="flex flex-wrap gap-3 items-center">
            <div className="relative flex-1 min-w-64">
              <Shield size={14} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-white/30" />
              <input
                value={search}
                onChange={e => setSearch(e.target.value)}
                placeholder="Search exhibitors, products, services..."
                className="w-full bg-[#0D1220] border border-white/10 focus:border-[#FFCD05]/50 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white outline-none transition-colors placeholder:text-white/25"
              />
            </div>
            <select value={sector} onChange={e => setSector(e.target.value)} className="bg-[#0D1220] border border-white/10 hover:border-white/20 rounded-xl px-4 py-2.5 text-sm text-white outline-none cursor-pointer transition-colors">
              {ALL_SECTORS_DIR.map(s => <option key={s}>{s}</option>)}
            </select>
            <select value={county} onChange={e => setCounty(e.target.value)} className="bg-[#0D1220] border border-white/10 hover:border-white/20 rounded-xl px-4 py-2.5 text-sm text-white outline-none cursor-pointer transition-colors">
              {counties.map(c => <option key={c}>{c}</option>)}
            </select>
            <select value={plan} onChange={e => setPlan(e.target.value)} className="bg-[#0D1220] border border-white/10 hover:border-white/20 rounded-xl px-4 py-2.5 text-sm text-white outline-none cursor-pointer transition-colors">
              <option value="all">All Plans</option>
              <option value="enterprise">Enterprise</option>
              <option value="pro">Pro</option>
              <option value="standard">Standard</option>
            </select>
            <select value={sortBy} onChange={e => setSortBy(e.target.value as typeof sortBy)} className="bg-[#0D1220] border border-white/10 hover:border-white/20 rounded-xl px-4 py-2.5 text-sm text-white outline-none cursor-pointer transition-colors">
              <option value="verified">Sort: Verified First</option>
              <option value="products">Sort: Most Products</option>
              <option value="name">Sort: A–Z</option>
            </select>
            <div className="text-xs text-white/30 ml-auto shrink-0">{filtered.length} results</div>
          </div>
        </div>
      </div>

      {/* Results Grid */}
      <div className="max-w-6xl mx-auto px-4 py-10">
        <AnimatePresence mode="popLayout">
          {filtered.length === 0 ? (
            <motion.div key="empty" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="text-center py-24 text-white/20">
              <Shield size={40} className="mx-auto mb-3 opacity-30" />
              <div className="font-bold text-lg">No exhibitors found</div>
              <div className="text-sm mt-1">Try adjusting your filters or search terms</div>
            </motion.div>
          ) : (
            <motion.div key="grid" className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {filtered.map((ex, i) => (
                <motion.div
                  key={ex.id}
                  layout
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, scale: 0.96 }}
                  transition={{ delay: i * 0.04 }}
                >
                  <TiltCard>
                    <div
                      onClick={() => setSelectedId(ex.id)}
                      className="bg-[#0D1220] border border-white/8 hover:border-white/20 rounded-2xl overflow-hidden cursor-pointer group transition-all hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/40"
                    >
                      {/* Banner */}
                      <div className="h-36 relative overflow-hidden">
                        <img src={u(ex.banner, 600, 200)} alt="" className="w-full h-full object-cover opacity-60 group-hover:opacity-80 transition-all group-hover:scale-105" />
                        <div className="absolute inset-0 bg-gradient-to-t from-[#0D1220] via-[#0D1220]/20 to-transparent" />
                        {ex.plan === "enterprise" && (
                          <div className="absolute top-3 right-3 bg-[#FFCD05] text-[#07090F] text-[10px] font-black px-2.5 py-1 rounded-full uppercase tracking-wider">Enterprise</div>
                        )}
                        {ex.plan === "pro" && (
                          <div className="absolute top-3 right-3 bg-[#0B1E57] border border-blue-400/30 text-blue-300 text-[10px] font-black px-2.5 py-1 rounded-full uppercase tracking-wider">Pro</div>
                        )}
                      </div>

                      {/* Logo + Info */}
                      <div className="px-5 pb-5">
                        <div className="flex items-end gap-3 -mt-6 mb-3">
                          <div className="w-14 h-14 rounded-xl border-2 border-[#0D1220] overflow-hidden bg-[#141B2E] shrink-0">
                            <img src={u(ex.logo, 112, 112)} alt="" className="w-full h-full object-cover" />
                          </div>
                          {ex.verified && (
                            <div className="mb-1 flex items-center gap-1 text-emerald-400">
                              <CheckCircle size={13} className="fill-emerald-900" />
                              <span className="text-[10px] font-bold">Verified</span>
                            </div>
                          )}
                        </div>

                        <h3 className="font-black text-white text-base leading-tight mb-1 group-hover:text-[#FFCD05] transition-colors">{ex.name}</h3>
                        <div className="flex items-center gap-1.5 text-xs text-white/40 mb-2">
                          <MapPin size={10} className="text-blue-300" />{ex.county}
                          <span className="text-white/15">·</span>
                          <Tag size={10} className="text-white/30" />{ex.sector}
                        </div>

                        <p className="text-white/45 text-xs leading-relaxed mb-4 line-clamp-2">{ex.tagline}</p>

                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-1 text-xs text-white/30">
                            <Package size={11} className="text-[#FFCD05]" />
                            <span className="font-bold text-white">{ex.products}</span> products
                          </div>
                          <div className="flex items-center gap-1 text-xs text-[#FFCD05] font-bold group-hover:gap-2 transition-all">
                            View Profile <ChevronRight size={12} />
                          </div>
                        </div>
                      </div>
                    </div>
                  </TiltCard>
                </motion.div>
              ))}
            </motion.div>
          )}
        </AnimatePresence>

        {/* CTA */}
        <div className="mt-16 text-center">
          <div className="inline-block bg-gradient-to-br from-[#0B1E57] to-[#050A1C] border border-blue-500/15 rounded-3xl px-10 py-8">
            <div className="font-black text-white text-xl mb-2">Are you an exhibitor?</div>
            <div className="text-white/40 text-sm mb-5">Create your listing and reach thousands of buyers across Kenya</div>
            <Btn variant="gold" onClick={() => navigate("login")}>Register as Exhibitor</Btn>
          </div>
        </div>
      </div>
    </PageWrap>
  );
}

// ─── EXHIBITOR PUBLIC PROFILE ─────────────────────────────────────────────────
export function ExhibitorPublicProfile({
  navigate,
  exhibitor: propExhibitor,
  onBack,
}: {
  navigate: (p: Page) => void;
  exhibitor?: typeof DIRECTORY_EXHIBITORS[0];
  onBack?: () => void;
}) {
  const ex = propExhibitor ?? DIRECTORY_EXHIBITORS[0];
  const exProducts = PRODUCTS.filter((_, i) => i < ex.products).slice(0, 8);
  const [activeTab, setActiveTab] = useState<"products" | "about" | "contact">("products");

  return (
    <PageWrap>
      {/* Back button */}
      <div className="max-w-6xl mx-auto px-4 pt-6">
        <button
          onClick={() => onBack ? onBack() : navigate("directory")}
          className="flex items-center gap-2 text-white/40 hover:text-white text-sm cursor-pointer transition-colors mb-6"
        >
          <ChevronLeft size={16} /> Back to Directory
        </button>
      </div>

      {/* Banner */}
      <div className="relative h-64 overflow-hidden">
        <img src={u(ex.banner, 1200, 400)} alt="" className="w-full h-full object-cover opacity-50" />
        <div className="absolute inset-0 bg-gradient-to-t from-[#07090F] via-[#07090F]/30 to-transparent" />
      </div>

      {/* Profile header */}
      <div className="max-w-6xl mx-auto px-4 -mt-16 relative z-10">
        <div className="flex flex-col md:flex-row md:items-end gap-5 mb-8">
          <div className="w-24 h-24 rounded-2xl border-4 border-[#07090F] overflow-hidden bg-[#141B2E] shrink-0">
            <img src={u(ex.logo, 192, 192)} alt="" className="w-full h-full object-cover" />
          </div>
          <div className="flex-1">
            <div className="flex items-center gap-3 flex-wrap mb-1">
              <h1 className="font-black text-white text-2xl md:text-3xl">{ex.name}</h1>
              {ex.verified && (
                <span className="flex items-center gap-1 bg-emerald-500/15 border border-emerald-500/25 px-2.5 py-1 rounded-full text-xs font-bold text-emerald-400">
                  <CheckCircle size={11} /> Verified
                </span>
              )}
            </div>
            <div className="flex items-center gap-3 text-sm text-white/40 flex-wrap">
              <span className="flex items-center gap-1"><MapPin size={12} className="text-blue-300" />{ex.county} County</span>
              <span className="text-white/15">·</span>
              <span className="flex items-center gap-1"><Tag size={12} />{ex.sector}</span>
              <span className="text-white/15">·</span>
              <span className="flex items-center gap-1"><Package size={12} className="text-[#FFCD05]" />{ex.products} products</span>
            </div>
          </div>
          <div className="flex gap-3 shrink-0">
            <Btn variant="dark" size="sm"><Globe size={13} /> Website</Btn>
            <Btn variant="primary" size="sm"><Mail size={13} /> Contact</Btn>
          </div>
        </div>

        {/* Tagline */}
        <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-5 mb-6">
          <p className="text-white/60 text-base italic leading-relaxed">"{ex.tagline}"</p>
        </div>

        {/* Stats bar */}
        <div className="grid grid-cols-3 gap-4 mb-8">
          {[["Products Listed", ex.products, Package], ["Profile Views", "4,820", Eye], ["Avg. Rating", "4.8 / 5", Star]].map(([l, v, Icon]) => (
            <div key={l as string} className="bg-[#0D1220] border border-white/8 rounded-2xl p-4 text-center">
              {/* @ts-ignore */}
              <Icon size={16} className="mx-auto mb-2 text-[#FFCD05]" />
              <div className="font-black text-white text-xl">{v as string}</div>
              <div className="text-white/30 text-xs mt-0.5">{l as string}</div>
            </div>
          ))}
        </div>

        {/* Tabs */}
        <div className="flex gap-2 mb-6 border-b border-white/8 pb-px">
          {(["products", "about", "contact"] as const).map(t => (
            <button
              key={t}
              onClick={() => setActiveTab(t)}
              className={`px-5 py-2.5 text-sm font-bold capitalize cursor-pointer transition-all border-b-2 -mb-px ${activeTab === t ? "text-[#FFCD05] border-[#FFCD05]" : "text-white/40 border-transparent hover:text-white/70"}`}
            >
              {t}
            </button>
          ))}
        </div>

        <AnimatePresence mode="wait">
          {activeTab === "products" && (
            <motion.div key="products" initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }}>
              {exProducts.length === 0 ? (
                <div className="text-center py-16 text-white/25">
                  <Package size={32} className="mx-auto mb-3 opacity-30" />
                  <div>No products listed yet</div>
                </div>
              ) : (
                <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-5 mb-12">
                  {exProducts.map(p => (
                    <TiltCard key={p.id}>
                      <div
                        onClick={() => navigate("product")}
                        className="bg-[#0D1220] border border-white/8 hover:border-white/20 rounded-2xl overflow-hidden cursor-pointer group hover:-translate-y-1 transition-all"
                      >
                        <div className="h-40 overflow-hidden">
                          <img src={u(p.img, 300, 200)} alt="" className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        </div>
                        <div className="p-4">
                          <div className="font-bold text-white text-sm mb-1 line-clamp-1">{p.name}</div>
                          <div className="flex items-center justify-between">
                            <div className="font-black text-[#FFCD05] text-sm">KES {fmt(p.price)}</div>
                            <div className="flex items-center gap-1 text-white/30 text-xs"><Star size={10} className="text-[#FFCD05] fill-[#FFCD05]" />{p.rating}</div>
                          </div>
                          <Pill color={p.stock > 0 ? "green" : "red"} className="mt-2">{p.stock > 0 ? "In stock" : "Out of stock"}</Pill>
                        </div>
                      </div>
                    </TiltCard>
                  ))}
                </div>
              )}
            </motion.div>
          )}

          {activeTab === "about" && (
            <motion.div key="about" initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }} className="mb-12">
              <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6 mb-5">
                <h3 className="font-bold text-white mb-3">About {ex.name}</h3>
                <p className="text-white/50 text-sm leading-relaxed">
                  {ex.name} is a {ex.verified ? "verified" : ""} {ex.sector} business based in {ex.county} County, Kenya.
                  With {ex.products} products listed on the KICC Global Exhibition Platform, we serve buyers and partners across East Africa and beyond.
                </p>
                <p className="text-white/40 text-sm leading-relaxed mt-3">
                  Our team is passionate about bringing Kenya's best to the world stage. We participate in major exhibitions at KICC and maintain an active presence across the platform's digital marketplace.
                </p>
              </div>
              <div className="grid md:grid-cols-2 gap-5">
                <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-5">
                  <h3 className="font-bold text-white text-sm mb-3">Business Details</h3>
                  {[["County", ex.county], ["Primary Sector", ex.sector], ["Plan", ex.plan.charAt(0).toUpperCase() + ex.plan.slice(1)], ["Verification", ex.verified ? "Verified Business" : "Unverified"]].map(([l, v]) => (
                    <div key={l} className="flex items-center justify-between py-2.5 border-b border-white/5 last:border-0 text-sm">
                      <span className="text-white/35">{l}</span>
                      <span className="font-semibold text-white">{v}</span>
                    </div>
                  ))}
                </div>
                <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-5">
                  <h3 className="font-bold text-white text-sm mb-3">Certifications</h3>
                  {ex.verified && (
                    <div className="flex items-center gap-2 p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl mb-2">
                      <CheckCircle size={14} className="text-emerald-400 shrink-0" />
                      <span className="text-emerald-300 text-xs font-semibold">KICC Verified Business</span>
                    </div>
                  )}
                  <div className="flex items-center gap-2 p-3 bg-blue-500/10 border border-blue-500/20 rounded-xl">
                    <Shield size={14} className="text-blue-300 shrink-0" />
                    <span className="text-blue-200 text-xs font-semibold">Kenya Business Registration</span>
                  </div>
                </div>
              </div>
            </motion.div>
          )}

          {activeTab === "contact" && (
            <motion.div key="contact" initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }} className="mb-12">
              <div className="grid md:grid-cols-2 gap-6">
                <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                  <h3 className="font-bold text-white mb-4">Contact {ex.name}</h3>
                  <div className="space-y-3 mb-5">
                    {[["Phone", "+254 722 000 000", Phone], ["Email", `info@${ex.name.toLowerCase().replace(/\s/g, "")}.co.ke`, Mail], ["County", ex.county, MapPin], ["Website", "View Website", Globe]].map(([l, v, Icon]) => (
                      <div key={l as string} className="flex items-center gap-3 py-2.5 border-b border-white/5 last:border-0">
                        {/* @ts-ignore */}
                        <Icon size={14} className="text-[#FFCD05] shrink-0" />
                        <div className="flex-1 min-w-0">
                          <div className="text-xs text-white/30">{l as string}</div>
                          <div className="text-sm font-semibold text-white truncate">{v as string}</div>
                        </div>
                      </div>
                    ))}
                  </div>
                  <Btn variant="primary" className="w-full"><Mail size={13} /> Send Message</Btn>
                </div>
                <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                  <h3 className="font-bold text-white mb-4">Quick Enquiry</h3>
                  <div className="space-y-3">
                    <input placeholder="Your name" className="w-full bg-[#141B2E] border border-white/8 focus:border-[#FFCD05]/50 rounded-xl px-4 py-2.5 text-sm text-white outline-none transition-colors placeholder:text-white/25" />
                    <input placeholder="Your email" type="email" className="w-full bg-[#141B2E] border border-white/8 focus:border-[#FFCD05]/50 rounded-xl px-4 py-2.5 text-sm text-white outline-none transition-colors placeholder:text-white/25" />
                    <textarea placeholder="Your message..." rows={4} className="w-full bg-[#141B2E] border border-white/8 focus:border-[#FFCD05]/50 rounded-xl px-4 py-2.5 text-sm text-white outline-none transition-colors placeholder:text-white/25 resize-none" />
                    <Btn variant="gold" className="w-full"><ExternalLink size={13} /> Send Enquiry</Btn>
                  </div>
                </div>
              </div>
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    </PageWrap>
  );
}
