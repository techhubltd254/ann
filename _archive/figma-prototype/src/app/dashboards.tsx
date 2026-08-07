import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";
import { Heart, Ticket, Play, ChevronLeft, Flag, Building, UserCog, Crown, Landmark } from "lucide-react";
import {
  type Page,
  COUNTIES, PRODUCTS, VENUES, EXHIBITIONS, chartRevenue, PIE_DATA, PIE_COLS,
  u, fmt,
  MapPin, Phone, Mail, Globe, Shield, ShoppingCart, Bell, Menu, X,
  ArrowRight, LogOut, Upload, ImageIcon, Plus, Tag,
  Layers, BarChart3, CreditCard, DollarSign, Users, TrendingUp,
  Package, Eye, CheckCircle, AlertCircle, Clock, Star,
  Edit3, Trash2, Download, FileText, Store, Settings,
  Wifi, QrCode, LayoutDashboard, Briefcase, Sliders,
  ToggleLeft, ToggleRight, ChevronDown, ChevronUp, ChevronRight,
  PlusCircle, Ban, Unlock, UserCheck, Sparkles, ExternalLink,
  MoreHorizontal, Tv, Award, Zap, Calendar,
  AreaChart, Area, BarChart, Bar, XAxis, YAxis, CartesianGrid,
  Tooltip, ResponsiveContainer, PieChart, Pie, Cell,
  Pill, Btn, KpiCard, DashShell, EditField, ImageEditor, TiltCard,
} from "./shared";

// ─── Local data ───────────────────────────────────────────────────────────────
const EXHIBITOR_INIT_PRODUCTS = PRODUCTS.slice(0, 5).map(p => ({ ...p }));

let _sectorId = 10;
const mkSectorId = () => `s${++_sectorId}`;

const INIT_SECTORS_COUNTY = [
  { id: "s1", name: "Agriculture & Agri-processing",  icon: "🌾", fields: ["Crop Type","Certification","Annual Output (tons)","Market Reach"],  entities: 24 },
  { id: "s2", name: "Tourism & Hospitality",           icon: "🏨", fields: ["Property Type","Star Rating","Capacity","Price Range (KES/night)"], entities: 38 },
  { id: "s3", name: "Handicrafts & Artisan Goods",    icon: "🎨", fields: ["Material","Technique","County of Origin","Price Range"],             entities: 17 },
  { id: "s4", name: "Health & Wellness",               icon: "🏥", fields: ["Specialty","Facilities","Insurance Accepted","Operating Hours"],    entities: 19 },
  { id: "s5", name: "Technology & ICT",                icon: "💻", fields: ["Services","Certifications","Staff Count","Operating Since"],         entities: 22 },
];

let _exhibitorId = 10;
const mkExhibitorId = () => `ex${++_exhibitorId}`;

const INIT_COUNTY_EXHIBITORS = [
  { id: "e1", name: "Mt Kenya Coffee Co.",   sector: "Agriculture", plan: "Gold",   status: "active",   joined: "Jan 2026", img: "1773858437375-e49a91b73ff1", contact: "info@mtkenyacoffee.co.ke" },
  { id: "e2", name: "Coastal Naturals Ltd",  sector: "Health",      plan: "Basic",  status: "active",   joined: "Feb 2026", img: "1776409933815-3497439f829a", contact: "hello@coastalnaturals.ke" },
  { id: "e3", name: "Enkiama Crafts",        sector: "Handicrafts", plan: "Silver", status: "expiring", joined: "Mar 2026", img: "1783024865247-d775f6b0b41b", contact: "enkiama@crafts.co.ke" },
  { id: "e4", name: "Heritage Pottery",      sector: "Handicrafts", plan: "Basic",  status: "pending",  joined: "Jul 2026", img: "1772411535291-aa5884035934", contact: "heritage@pottery.co.ke" },
  { id: "e5", name: "Savanna Safaris",       sector: "Tourism",     plan: "Gold",   status: "active",   joined: "Dec 2025", img: "1709402606682-400133d92ab2", contact: "bookings@savanasafaris.ke" },
];

let _eventId = 10;
const mkEventId = () => `ev${++_eventId}`;

const INIT_COUNTY_EVENTS = [
  { id: "ev1", name: "Nairobi County Business Expo 2026", date: "5-7 Sep 2026",  venue: "KICC Tsavo Hall",   status: "upcoming", booths: 80 },
  { id: "ev2", name: "County Farmers Market",             date: "Every Saturday", venue: "County Grounds",    status: "recurring", booths: 40 },
  { id: "ev3", name: "Youth SME Showcase 2026",           date: "12 Aug 2026",   venue: "County Hall",       status: "upcoming", booths: 30 },
];

const INIT_APPROVAL_QUEUE = [
  { id: "a1", name: "Maasai Naturals Ltd",    county: "Kajiado", sector: "Beauty & Wellness", submitted: "24 Jul 2026", type: "exhibitor", img: "1602009786436-96b827675d32" },
  { id: "a2", name: "Lake Victoria Fisheries", county: "Kisumu",  sector: "Agri & Food",       submitted: "23 Jul 2026", type: "exhibitor", img: "1751568928684-9fa66911be90" },
  { id: "a3", name: "Mt Elgon Honey Co.",      county: "Bungoma", sector: "Agriculture",       submitted: "21 Jul 2026", type: "exhibitor", img: "1776409933876-022c6cb0baf6" },
  { id: "a4", name: "Lamu Heritage Tours",     county: "Lamu",    sector: "Tourism",           submitted: "19 Jul 2026", type: "exhibitor", img: "1652511931085-97666ab442ce" },
  { id: "a5", name: "Nakuru Dairy Farmers",    county: "Nakuru",  sector: "Agriculture",       submitted: "18 Jul 2026", type: "exhibitor", img: "1554490594-0e5b97120099"    },
];

const INIT_COUNTY_ADMINS = [
  { id: "ca1", county: "Nairobi",  name: "Grace Mwangi",    role: "County Director",     email: "grace@nairobi.go.ke",  status: "active",    img: "1611432579402-7037e3e2c1e4", exhibitors: 142, joinDate: "Jan 2025" },
  { id: "ca2", county: "Mombasa",  name: "Ali Hassan",      role: "County Director",     email: "ali@mombasa.go.ke",    status: "active",    img: "1618085219724-c59ba48e08cd", exhibitors: 87,  joinDate: "Mar 2025" },
  { id: "ca3", county: "Kisumu",   name: "Janet Ochieng",   role: "County Coordinator",  email: "janet@kisumu.go.ke",   status: "active",    img: "1573497019418-b400bb3ab074", exhibitors: 63,  joinDate: "Feb 2025" },
  { id: "ca4", county: "Nakuru",   name: "Peter Kamau",     role: "County Coordinator",  email: "peter@nakuru.go.ke",   status: "suspended", img: "1563132337-f159f484226c",    exhibitors: 45,  joinDate: "Jun 2025" },
  { id: "ca5", county: "Kilifi",   name: "Fatuma Salim",    role: "County Director",     email: "fatuma@kilifi.go.ke",  status: "active",    img: "1602009786436-96b827675d32", exhibitors: 38,  joinDate: "Apr 2025" },
  { id: "ca6", county: "Laikipia", name: "John Mutuku",     role: "County Coordinator",  email: "john@laikipia.go.ke",  status: "pending",   img: "1611432579402-7037e3e2c1e4", exhibitors: 0,   joinDate: "Jul 2026" },
];

const INIT_NATIONAL_ENTITIES = [
  { id: "n1", name: "Kenya Tourism Board",              ministry: "Tourism",        type: "Parastatal",    status: "active",  contact: "info@ktb.go.ke",          exhibitors: 0,  img: "1709402606682-400133d92ab2" },
  { id: "n2", name: "Kenya Investment Authority",       ministry: "Trade",          type: "Government",    status: "active",  contact: "info@keninvest.go.ke",    exhibitors: 0,  img: "1611144727915-ef30a08aaeb3" },
  { id: "n3", name: "KEPHIS",                          ministry: "Agriculture",    type: "Regulatory",    status: "active",  contact: "kephis@kephis.org",       exhibitors: 0,  img: "1776409933815-3497439f829a" },
  { id: "n4", name: "Kenya Medical Research Institute", ministry: "Health",         type: "Research",      status: "active",  contact: "info@kemri.go.ke",        exhibitors: 0,  img: "1573497019418-b400bb3ab074" },
  { id: "n5", name: "Export Promotion Council",        ministry: "Trade",          type: "Government",    status: "pending", contact: "info@epckenya.org",       exhibitors: 0,  img: "1611432579402-7037e3e2c1e4" },
];

const INIT_PRIVATE_ENTITIES = [
  { id: "p1", name: "Safaricom PLC",         sector: "Technology",  type: "Corporate",  status: "active",  contact: "corporate@safaricom.co.ke", revenue: "KES 2.4M", img: "1611144727915-ef30a08aaeb3" },
  { id: "p2", name: "Equity Bank Kenya",     sector: "Finance",     type: "Corporate",  status: "active",  contact: "group@equitybank.co.ke",    revenue: "KES 1.8M", img: "1741991110666-88115e724741" },
  { id: "p3", name: "Twiga Foods Ltd",       sector: "Agri-Tech",   type: "Startup",    status: "active",  contact: "info@twigafoods.com",       revenue: "KES 450K", img: "1773858437375-e49a91b73ff1" },
  { id: "p4", name: "M-KOPA Solar",          sector: "Energy",      type: "Corporate",  status: "pending", contact: "info@m-kopa.com",           revenue: "—",        img: "1641991109902-98bf764fb35d" },
];

const PLATFORM_CONTENT = {
  heroTitle: "Where Kenya Does Business",
  heroSubtitle: "Your gateway to 47 counties, thousands of exhibitors, and Kenya's largest digital marketplace",
  heroImg: "1775314054195-85f31de0c944",
  stat1Label: "Years of Excellence", stat1Value: "52",
  stat2Label: "Kenya Counties",      stat2Value: "47",
  stat3Label: "Digital Screens",     stat3Value: "18",
  stat4Label: "Events per Year",     stat4Value: "200",
  commissionKICC: "15", commissionCounty: "70", commissionSeller: "85",
};

// ─── Reusable modal shell ─────────────────────────────────────────────────────
function Modal({ title, onClose, children }: { title: string; onClose: () => void; children: React.ReactNode }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} onClick={onClose} className="absolute inset-0 bg-black/70 backdrop-blur-sm" />
      <motion.div initial={{ opacity: 0, scale: 0.92, y: 20 }} animate={{ opacity: 1, scale: 1, y: 0 }} exit={{ opacity: 0, scale: 0.92 }} transition={{ duration: 0.25 }}
        className="relative bg-[#0D1220] border border-white/12 rounded-2xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">
        <div className="flex items-center justify-between mb-5">
          <h3 className="font-black text-white text-lg">{title}</h3>
          <button onClick={onClose} className="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/8 text-white/40 hover:text-white cursor-pointer"><X size={16} /></button>
        </div>
        {children}
      </motion.div>
    </div>
  );
}

function FormField({ label, placeholder, value, onChange, type = "text" }: { label: string; placeholder?: string; value: string; onChange: (v: string) => void; type?: string }) {
  return (
    <div className="mb-4">
      <label className="text-[10px] font-bold text-white/35 uppercase tracking-wider block mb-1.5">{label}</label>
      <input type={type} value={value} placeholder={placeholder} onChange={e => onChange(e.target.value)}
        className="w-full px-4 h-11 rounded-xl bg-[#141B2E] border border-white/10 text-white/80 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05] transition-all" />
    </div>
  );
}

function FormSelect({ label, value, onChange, options }: { label: string; value: string; onChange: (v: string) => void; options: string[] }) {
  return (
    <div className="mb-4">
      <label className="text-[10px] font-bold text-white/35 uppercase tracking-wider block mb-1.5">{label}</label>
      <select value={value} onChange={e => onChange(e.target.value)}
        className="w-full px-4 h-11 rounded-xl bg-[#141B2E] border border-white/10 text-white/80 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05] appearance-none cursor-pointer">
        {options.map(o => <option key={o} value={o}>{o}</option>)}
      </select>
    </div>
  );
}

// ─── Status badge ─────────────────────────────────────────────────────────────
function StatusBadge({ status }: { status: string }) {
  const map: Record<string, string> = {
    active: "bg-emerald-500/15 text-emerald-400 border-emerald-500/25",
    pending: "bg-[#FFCD05]/15 text-[#FFCD05] border-[#FFCD05]/25",
    suspended: "bg-[#901C1E]/15 text-[#f87171] border-[#901C1E]/25",
    expiring: "bg-orange-500/15 text-orange-400 border-orange-500/25",
    recurring: "bg-blue-500/15 text-blue-400 border-blue-500/25",
    upcoming: "bg-purple-500/15 text-purple-400 border-purple-500/25",
  };
  return <span className={`text-[10px] font-bold px-2.5 py-1 rounded-full border capitalize ${map[status] ?? map.pending}`}>{status}</span>;
}

// ─── BUYER DASHBOARD ──────────────────────────────────────────────────────────
export function DashBuyer({ navigate }: { navigate: (p: Page) => void }) {
  return (
    <DashShell title="My Account" role="Buyer / Visitor" navigate={navigate} navItems={[
      { icon: LayoutDashboard, label: "Overview", active: true },
      { icon: Package, label: "My Orders" }, { icon: Ticket, label: "Tickets" },
      { icon: Heart, label: "Wishlist" }, { icon: Settings, label: "Profile" },
    ]}>
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <KpiCard label="Orders placed" value="14" change="+3" icon={Package} />
        <KpiCard label="Active tickets" value="2" icon={Ticket} accent="#0B1E57" />
        <KpiCard label="Wishlist items" value="8" icon={Heart} accent="#FFCD05" />
        <KpiCard label="Loyalty points" value="2,340" icon={Award} accent="#2D6A4F" />
      </div>
      <div className="grid lg:grid-cols-2 gap-6">
        <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
          <h3 className="font-bold text-white mb-5">Recent Orders</h3>
          {[["KDP-2026-08812","Kenyan AA Coffee ×2","KES 2,400","delivered"],["KDP-2026-08743","Maasai Shuka Blanket","KES 3,500","shipped"],["KDP-2026-08612","Coconut Body Oil Set","KES 890","processing"]].map(([id,name,amt,st]) => (
            <div key={id as string} className="flex items-center gap-3 py-3 border-b border-white/5 last:border-0">
              <div className="w-10 h-10 bg-[#141B2E] rounded-xl flex items-center justify-center shrink-0"><Package size={15} className="text-white/30" /></div>
              <div className="flex-1 min-w-0"><div className="font-semibold text-white text-sm line-clamp-1">{name as string}</div><div className="text-xs text-white/30 font-mono">{id as string}</div></div>
              <div className="text-right shrink-0">
                <div className="font-bold text-white text-sm">{amt as string}</div>
                <StatusBadge status={st as string} />
              </div>
            </div>
          ))}
        </div>
        <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
          <h3 className="font-bold text-white mb-5">Saved Products</h3>
          {PRODUCTS.slice(0, 4).map(p => (
            <div key={p.id} className="flex items-center gap-3 py-2 border-b border-white/5 last:border-0">
              <div className="w-10 h-10 rounded-xl overflow-hidden shrink-0 bg-[#141B2E]"><img src={u(p.img, 80, 80)} alt="" className="w-full h-full object-cover" /></div>
              <div className="flex-1 min-w-0"><div className="text-sm font-semibold text-white line-clamp-1">{p.name}</div><div className="text-xs text-white/30">{p.county}</div></div>
              <div className="font-bold text-[#FFCD05] text-sm shrink-0">KES {fmt(p.price)}</div>
            </div>
          ))}
        </div>
      </div>
    </DashShell>
  );
}

// ─── EXHIBITOR DASHBOARD ──────────────────────────────────────────────────────
export function DashExhibitor({ navigate }: { navigate: (p: Page) => void }) {
  const [tab, setTab] = useState("overview");
  const [products, setProducts] = useState(EXHIBITOR_INIT_PRODUCTS);
  const [showAddProduct, setShowAddProduct] = useState(false);
  const [newProd, setNewProd] = useState({ name: "", price: "", category: "", county: "" });
  const [features, setFeatures] = useState({ liveStream: true, marketplace: true, directory: true, analytics: false, chat: false });
  const tabs = ["overview","products","profile","analytics","features"];

  const addProduct = () => {
    if (!newProd.name || !newProd.price) return;
    setProducts(p => [...p, { id: `p${Date.now()}`, name: newProd.name, price: Number(newProd.price), img: "1776409933815-3497439f829a", category: newProd.category || "General", county: newProd.county || "Nairobi", seller: "My Store", rating: 0, reviews: 0, stock: 10 }]);
    setNewProd({ name: "", price: "", category: "", county: "" });
    setShowAddProduct(false);
  };

  return (
    <DashShell title="Exhibitor Dashboard" role="Verified Exhibitor" initials="EX" accentColor="#2D6A4F" navigate={navigate} navItems={tabs.map((t, i) => ({ icon: [LayoutDashboard, Package, Edit3, BarChart3, Sliders][i], label: t.charAt(0).toUpperCase()+t.slice(1), active: tab === t, onClick: () => setTab(t) }))}>
      <AnimatePresence mode="wait">
        <motion.div key={tab} initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -6 }} transition={{ duration: 0.22 }}>
          {tab === "overview" && (
            <>
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <KpiCard label="Profile views" value="1,248" change="+18%" icon={Eye} />
                <KpiCard label="Products" value={String(products.length)} icon={Package} accent="#2D6A4F" />
                <KpiCard label="Enquiries" value="34" change="+5" icon={Mail} accent="#0B1E57" />
                <KpiCard label="This month revenue" value="KES 48K" icon={DollarSign} accent="#FFCD05" />
              </div>
              <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6 mb-6">
                <h3 className="font-bold text-white mb-4">Revenue Trend</h3>
                <ResponsiveContainer width="100%" height={200}>
                  <AreaChart data={chartRevenue}><defs><linearGradient id="eg" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#2D6A4F" stopOpacity={0.3}/><stop offset="95%" stopColor="#2D6A4F" stopOpacity={0}/></linearGradient></defs><CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.04)"/><XAxis dataKey="m" stroke="rgba(255,255,255,0.2)" fontSize={11}/><YAxis stroke="rgba(255,255,255,0.2)" fontSize={11}/><Tooltip contentStyle={{ background:"#141B2E", border:"1px solid rgba(255,255,255,0.08)", borderRadius:12, color:"#fff" }}/><Area type="monotone" dataKey="v" stroke="#2D6A4F" fill="url(#eg)" strokeWidth={2}/></AreaChart>
                </ResponsiveContainer>
              </div>
            </>
          )}

          {tab === "products" && (
            <div>
              <div className="flex items-center justify-between mb-5">
                <h3 className="font-bold text-white text-lg">My Products <span className="text-white/30 font-normal text-base">({products.length})</span></h3>
                <Btn variant="primary" size="sm" onClick={() => setShowAddProduct(true)}><Plus size={15} /> Add Product</Btn>
              </div>
              <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <AnimatePresence>
                  {products.map(p => (
                    <motion.div key={p.id} initial={{ opacity: 0, scale: 0.92 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.88 }} className="bg-[#0D1220] border border-white/8 rounded-2xl overflow-hidden group">
                      <div className="h-40 overflow-hidden bg-[#141B2E] relative">
                        <img src={u(p.img, 400, 300)} alt={p.name} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        <button onClick={() => setProducts(ps => ps.filter(x => x.id !== p.id))}
                          className="absolute top-2 right-2 w-7 h-7 bg-[#901C1E]/90 backdrop-blur rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                          <Trash2 size={12} className="text-white" />
                        </button>
                      </div>
                      <div className="p-4">
                        <div className="text-[10px] font-bold text-[#FFCD05] uppercase tracking-wider mb-1">{p.category}</div>
                        <div className="font-bold text-white text-sm line-clamp-2 mb-2">{p.name}</div>
                        <div className="flex items-center justify-between">
                          <span className="font-black text-[#FFCD05]">KES {fmt(p.price)}</span>
                          <button className="text-white/30 hover:text-white cursor-pointer"><Edit3 size={13} /></button>
                        </div>
                      </div>
                    </motion.div>
                  ))}
                </AnimatePresence>
              </div>
            </div>
          )}

          {tab === "profile" && (
            <div className="grid lg:grid-cols-2 gap-6">
              <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 className="font-bold text-white mb-5">Company Profile</h3>
                <ImageEditor label="Company Logo" hint="Square PNG or JPG · min 400×400px" />
                <EditField label="Company Name" defaultValue="Mt Kenya Coffee Co." />
                <EditField label="Tagline" defaultValue="Premium single-origin Kenyan coffee" />
                <EditField label="Description" defaultValue="We source, roast and export Kenya's finest AA coffee from the slopes of Mount Kenya." multiline />
                <EditField label="Website" defaultValue="https://mtkenyacoffee.co.ke" />
              </div>
              <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 className="font-bold text-white mb-5">Contact & Location</h3>
                <EditField label="Contact Email" defaultValue="info@mtkenyacoffee.co.ke" />
                <EditField label="Phone" defaultValue="+254 722 345 678" />
                <EditField label="Physical Address" defaultValue="Nyeri Town, Nyeri County" />
                <ImageEditor label="Banner / Cover Photo" hint="Landscape · min 1200×400px" />
              </div>
            </div>
          )}

          {tab === "analytics" && (
            <div className="grid lg:grid-cols-2 gap-6">
              <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 className="font-bold text-white mb-4">Traffic Sources</h3>
                <ResponsiveContainer width="100%" height={220}>
                  <PieChart><Pie data={PIE_DATA} cx="50%" cy="50%" outerRadius={80} dataKey="v" label={({ name, percent }) => `${name} ${(percent*100).toFixed(0)}%`} labelLine={false}>
                    {PIE_DATA.map((_,i) => <Cell key={i} fill={PIE_COLS[i % PIE_COLS.length]} />)}
                  </Pie><Tooltip contentStyle={{ background:"#141B2E", border:"1px solid rgba(255,255,255,0.08)", borderRadius:12, color:"#fff" }} /></PieChart>
                </ResponsiveContainer>
              </div>
              <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 className="font-bold text-white mb-4">Monthly Enquiries</h3>
                <ResponsiveContainer width="100%" height={220}>
                  <BarChart data={chartRevenue}><CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.04)"/><XAxis dataKey="m" stroke="rgba(255,255,255,0.2)" fontSize={11}/><YAxis stroke="rgba(255,255,255,0.2)" fontSize={11}/><Tooltip contentStyle={{ background:"#141B2E", border:"1px solid rgba(255,255,255,0.08)", borderRadius:12, color:"#fff" }}/><Bar dataKey="v" fill="#2D6A4F" radius={[6,6,0,0]}/></BarChart>
                </ResponsiveContainer>
              </div>
            </div>
          )}

          {tab === "features" && (
            <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
              <h3 className="font-bold text-white mb-6">Feature Toggles</h3>
              <div className="space-y-4">
                {Object.entries(features).map(([key, val]) => (
                  <div key={key} className="flex items-center justify-between py-3 border-b border-white/5 last:border-0">
                    <div><div className="font-semibold text-white capitalize">{key.replace(/([A-Z])/g, " $1")}</div><div className="text-xs text-white/30 mt-0.5">Enable {key.toLowerCase()} for your profile</div></div>
                    <button onClick={() => setFeatures(f => ({ ...f, [key]: !val }))} className="cursor-pointer shrink-0">
                      {val ? <ToggleRight size={32} className="text-[#2D6A4F]" /> : <ToggleLeft size={32} className="text-white/20" />}
                    </button>
                  </div>
                ))}
              </div>
            </div>
          )}
        </motion.div>
      </AnimatePresence>

      <AnimatePresence>
        {showAddProduct && (
          <Modal title="Add New Product" onClose={() => setShowAddProduct(false)}>
            <FormField label="Product Name" placeholder="e.g. Nyeri AA Coffee 250g" value={newProd.name} onChange={v => setNewProd(p => ({ ...p, name: v }))} />
            <FormField label="Price (KES)" placeholder="e.g. 1200" value={newProd.price} onChange={v => setNewProd(p => ({ ...p, price: v }))} type="number" />
            <FormSelect label="Category" value={newProd.category} onChange={v => setNewProd(p => ({ ...p, category: v }))} options={["Food & Beverages","Crafts & Textiles","Beauty & Wellness","Arts & Décor","Jewellery","Agriculture","General"]} />
            <FormField label="County" placeholder="e.g. Nyeri" value={newProd.county} onChange={v => setNewProd(p => ({ ...p, county: v }))} />
            <div className="flex gap-3 mt-2">
              <Btn variant="primary" className="flex-1" onClick={addProduct}>Add Product</Btn>
              <Btn variant="dark" onClick={() => setShowAddProduct(false)}>Cancel</Btn>
            </div>
          </Modal>
        )}
      </AnimatePresence>
    </DashShell>
  );
}

// ─── SELLER DASHBOARD ─────────────────────────────────────────────────────────
export function DashSeller({ navigate }: { navigate: (p: Page) => void }) {
  const [tab, setTab] = useState("overview");
  const tabs = ["overview","orders","products","payouts"];
  return (
    <DashShell title="Seller Hub" role="Marketplace Seller" initials="SL" accentColor="#901C1E" navigate={navigate} navItems={tabs.map((t,i) => ({ icon:[LayoutDashboard,Package,Store,CreditCard][i], label:t.charAt(0).toUpperCase()+t.slice(1), active:tab===t, onClick:()=>setTab(t) }))}>
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <KpiCard label="Total sales" value="KES 284K" change="+22%" icon={TrendingUp} accent="#901C1E" />
        <KpiCard label="Orders this month" value="47" change="+8" icon={Package} />
        <KpiCard label="Products listed" value="23" icon={Store} accent="#0B1E57" />
        <KpiCard label="Rating" value="4.8★" icon={Star} accent="#FFCD05" />
      </div>
      <div className="grid lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 bg-[#0D1220] border border-white/8 rounded-2xl p-6">
          <h3 className="font-bold text-white mb-4">Revenue (KES '000)</h3>
          <ResponsiveContainer width="100%" height={200}>
            <AreaChart data={chartRevenue}><defs><linearGradient id="sg" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#901C1E" stopOpacity={0.35}/><stop offset="95%" stopColor="#901C1E" stopOpacity={0}/></linearGradient></defs><CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.04)"/><XAxis dataKey="m" stroke="rgba(255,255,255,0.2)" fontSize={11}/><YAxis stroke="rgba(255,255,255,0.2)" fontSize={11}/><Tooltip contentStyle={{ background:"#141B2E", border:"1px solid rgba(255,255,255,0.08)", borderRadius:12, color:"#fff" }}/><Area type="monotone" dataKey="v" stroke="#901C1E" fill="url(#sg)" strokeWidth={2}/></AreaChart>
          </ResponsiveContainer>
        </div>
        <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
          <h3 className="font-bold text-white mb-4">Pending Orders</h3>
          {[["Maasai Shuka ×1","Kajiado","KES 3,500"],["Coffee 500g ×3","Nyeri","KES 3,600"],["Body Oil Set ×2","Mombasa","KES 1,780"]].map(([n,c,a]) => (
            <div key={n as string} className="py-3 border-b border-white/5 last:border-0">
              <div className="flex justify-between"><span className="text-sm font-semibold text-white">{n as string}</span><span className="text-sm font-bold text-[#FFCD05]">{a as string}</span></div>
              <div className="text-xs text-white/30 mt-0.5">{c as string} County</div>
              <div className="flex gap-2 mt-2">
                <Btn variant="primary" size="sm">Accept</Btn>
                <Btn variant="dark" size="sm">Reject</Btn>
              </div>
            </div>
          ))}
        </div>
      </div>
    </DashShell>
  );
}

// ─── COUNTY ADMIN DASHBOARD ───────────────────────────────────────────────────
export function DashCounty({ navigate }: { navigate: (p: Page) => void }) {
  const [tab, setTab] = useState("overview");
  const [sectors, setSectors] = useState(INIT_SECTORS_COUNTY);
  const [exhibitors, setExhibitors] = useState(INIT_COUNTY_EXHIBITORS);
  const [events, setEvents] = useState(INIT_COUNTY_EVENTS);
  const [approvalQueue, setApprovalQueue] = useState(INIT_APPROVAL_QUEUE.filter(a => a.county === "Nairobi" || true).slice(0,3));

  // Modals
  const [showAddSector, setShowAddSector]         = useState(false);
  const [showAddExhibitor, setShowAddExhibitor]   = useState(false);
  const [showAddEvent, setShowAddEvent]           = useState(false);
  const [confirmDelete, setConfirmDelete]         = useState<{ type: string; id: string; name: string } | null>(null);

  // New sector form
  const [newSector, setNewSector] = useState({ name: "", icon: "🏢", field1: "", field2: "", field3: "", field4: "" });
  // New exhibitor form
  const [newEx, setNewEx] = useState({ name: "", sector: "Agriculture", plan: "Basic", contact: "" });
  // New event form
  const [newEv, setNewEv] = useState({ name: "", date: "", venue: "", booths: "" });

  const addSector = () => {
    if (!newSector.name) return;
    setSectors(s => [...s, { id: mkSectorId(), name: newSector.name, icon: newSector.icon, fields: [newSector.field1, newSector.field2, newSector.field3, newSector.field4].filter(Boolean), entities: 0 }]);
    setNewSector({ name: "", icon: "🏢", field1: "", field2: "", field3: "", field4: "" });
    setShowAddSector(false);
  };

  const addExhibitor = () => {
    if (!newEx.name) return;
    setExhibitors(e => [...e, { id: mkExhibitorId(), name: newEx.name, sector: newEx.sector, plan: newEx.plan, status: "active", joined: "Jul 2026", img: "1776409933815-3497439f829a", contact: newEx.contact }]);
    setNewEx({ name: "", sector: "Agriculture", plan: "Basic", contact: "" });
    setShowAddExhibitor(false);
  };

  const addEvent = () => {
    if (!newEv.name) return;
    setEvents(e => [...e, { id: mkEventId(), name: newEv.name, date: newEv.date || "TBD", venue: newEv.venue || "County Grounds", status: "upcoming", booths: Number(newEv.booths) || 0 }]);
    setNewEv({ name: "", date: "", venue: "", booths: "" });
    setShowAddEvent(false);
  };

  const deleteItem = () => {
    if (!confirmDelete) return;
    if (confirmDelete.type === "sector") setSectors(s => s.filter(x => x.id !== confirmDelete.id));
    if (confirmDelete.type === "exhibitor") setExhibitors(e => e.filter(x => x.id !== confirmDelete.id));
    if (confirmDelete.type === "event") setEvents(e => e.filter(x => x.id !== confirmDelete.id));
    setConfirmDelete(null);
  };

  const approveExhibitor = (id: string) => {
    setApprovalQueue(q => q.filter(a => a.id !== id));
    setExhibitors(e => [...e, { id: mkExhibitorId(), name: INIT_APPROVAL_QUEUE.find(a => a.id === id)?.name || "New Exhibitor", sector: "General", plan: "Basic", status: "active", joined: "Jul 2026", img: "1776409933815-3497439f829a", contact: "" }]);
  };

  const tabs = ["overview","sectors","exhibitors","events","approvals","profile","analytics"];

  return (
    <DashShell title="County Admin" role="Nairobi County Administrator" initials="CA" accentColor="#0B1E57" navigate={navigate}
      navItems={tabs.map((t,i) => ({ icon:[LayoutDashboard,Layers,Store,Calendar,CheckCircle,Edit3,BarChart3][i], label:["Overview","Sectors","Exhibitors","Events","Approvals","Profile","Analytics"][i], active:tab===t, onClick:()=>setTab(t) }))}>

      <AnimatePresence mode="wait">
        <motion.div key={tab} initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -6 }} transition={{ duration: 0.22 }}>

          {/* OVERVIEW */}
          {tab === "overview" && (
            <>
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <KpiCard label="Registered Exhibitors" value={String(exhibitors.length)} change="+4 this month" icon={Store} accent="#0B1E57" />
                <KpiCard label="Active Sectors" value={String(sectors.length)} icon={Layers} />
                <KpiCard label="Upcoming Events" value={String(events.filter(e => e.status === "upcoming").length)} icon={Calendar} accent="#FFCD05" />
                <KpiCard label="Pending Approvals" value={String(approvalQueue.length)} icon={AlertCircle} accent="#901C1E" />
              </div>
              <div className="grid lg:grid-cols-2 gap-6">
                <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                  <h3 className="font-bold text-white mb-4">County Growth</h3>
                  <ResponsiveContainer width="100%" height={200}>
                    <AreaChart data={chartRevenue}><defs><linearGradient id="cg" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#0B1E57" stopOpacity={0.4}/><stop offset="95%" stopColor="#0B1E57" stopOpacity={0}/></linearGradient></defs><CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.04)"/><XAxis dataKey="m" stroke="rgba(255,255,255,0.2)" fontSize={11}/><YAxis stroke="rgba(255,255,255,0.2)" fontSize={11}/><Tooltip contentStyle={{ background:"#141B2E", border:"1px solid rgba(255,255,255,0.08)", borderRadius:12, color:"#fff" }}/><Area type="monotone" dataKey="v" stroke="#0B1E57" fill="url(#cg)" strokeWidth={2}/></AreaChart>
                  </ResponsiveContainer>
                </div>
                <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                  <h3 className="font-bold text-white mb-4">Sector Distribution</h3>
                  <ResponsiveContainer width="100%" height={200}>
                    <PieChart><Pie data={sectors.map(s => ({ name: s.name, v: s.entities }))} cx="50%" cy="50%" outerRadius={80} dataKey="v" label={({ name }) => name.split(" ")[0]} labelLine={false}>
                      {sectors.map((_,i) => <Cell key={i} fill={PIE_COLS[i % PIE_COLS.length]} />)}
                    </Pie><Tooltip contentStyle={{ background:"#141B2E", border:"1px solid rgba(255,255,255,0.08)", borderRadius:12, color:"#fff" }}/></PieChart>
                  </ResponsiveContainer>
                </div>
              </div>
            </>
          )}

          {/* SECTORS */}
          {tab === "sectors" && (
            <div>
              <div className="flex items-center justify-between mb-5">
                <h3 className="font-bold text-white text-lg">County Sectors <span className="text-white/30 font-normal text-base">({sectors.length})</span></h3>
                <Btn variant="primary" size="sm" onClick={() => setShowAddSector(true)}><Plus size={15} /> Add Sector</Btn>
              </div>
              <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <AnimatePresence>
                  {sectors.map(s => (
                    <motion.div key={s.id} initial={{ opacity: 0, scale: 0.92 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.88 }}
                      className="bg-[#0D1220] border border-white/8 hover:border-[#0B1E57]/50 rounded-2xl p-5 group transition-all relative">
                      <button onClick={() => setConfirmDelete({ type: "sector", id: s.id, name: s.name })}
                        className="absolute top-3 right-3 w-7 h-7 bg-[#901C1E]/80 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                        <Trash2 size={12} className="text-white" />
                      </button>
                      <div className="text-3xl mb-3">{s.icon}</div>
                      <div className="font-bold text-white text-sm mb-1">{s.name}</div>
                      <div className="text-white/35 text-xs mb-3">{s.entities} entities registered</div>
                      <div className="space-y-1">
                        {s.fields.map(f => <div key={f} className="text-[10px] text-white/25 flex items-center gap-1"><Tag size={9} className="text-[#0B1E57]" />{f}</div>)}
                      </div>
                      <button className="mt-4 text-[10px] font-bold text-[#0B1E57] hover:text-[#FFCD05] cursor-pointer transition-colors flex items-center gap-1"><Edit3 size={10}/> Edit fields</button>
                    </motion.div>
                  ))}
                </AnimatePresence>
              </div>
            </div>
          )}

          {/* EXHIBITORS */}
          {tab === "exhibitors" && (
            <div>
              <div className="flex items-center justify-between mb-5">
                <h3 className="font-bold text-white text-lg">Exhibitors <span className="text-white/30 font-normal text-base">({exhibitors.length})</span></h3>
                <Btn variant="primary" size="sm" onClick={() => setShowAddExhibitor(true)}><Plus size={15} /> Add Exhibitor</Btn>
              </div>
              <div className="overflow-hidden rounded-2xl border border-white/8">
                <table className="w-full">
                  <thead><tr className="bg-[#141B2E]">{["Logo","Name","Sector","Plan","Status","Joined","Actions"].map(h => <th key={h} className="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-left">{h}</th>)}</tr></thead>
                  <tbody>
                    <AnimatePresence>
                      {exhibitors.map(e => (
                        <motion.tr key={e.id} initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0, height: 0 }}
                          className="border-b border-white/5 last:border-0 hover:bg-white/2 transition-colors group">
                          <td className="px-4 py-3"><div className="w-9 h-9 rounded-xl overflow-hidden bg-[#141B2E]"><img src={u(e.img, 72, 72)} alt="" className="w-full h-full object-cover" /></div></td>
                          <td className="px-4 py-3"><div className="font-semibold text-white text-sm">{e.name}</div><div className="text-xs text-white/30">{e.contact}</div></td>
                          <td className="px-4 py-3 text-sm text-white/60">{e.sector}</td>
                          <td className="px-4 py-3"><span className={`text-[10px] font-bold px-2.5 py-1 rounded-full border ${e.plan==="Gold"?"bg-[#FFCD05]/15 text-[#FFCD05] border-[#FFCD05]/25":e.plan==="Silver"?"bg-white/10 text-white/60 border-white/15":"bg-[#0B1E57]/20 text-blue-400 border-blue-500/25"}`}>{e.plan}</span></td>
                          <td className="px-4 py-3"><StatusBadge status={e.status} /></td>
                          <td className="px-4 py-3 text-sm text-white/40">{e.joined}</td>
                          <td className="px-4 py-3">
                            <div className="flex items-center gap-1">
                              <button className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-[#0B1E57]/40 cursor-pointer text-white/30 hover:text-blue-400 transition-colors" title="Edit"><Edit3 size={13} /></button>
                              <button onClick={() => setConfirmDelete({ type:"exhibitor", id: e.id, name: e.name })} className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-[#901C1E]/30 cursor-pointer text-white/30 hover:text-[#f87171] transition-colors" title="Remove"><Trash2 size={13} /></button>
                              {e.status === "active" ? <button className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-orange-500/20 cursor-pointer text-white/30 hover:text-orange-400 transition-colors" title="Suspend"><Ban size={13} /></button>
                               : <button className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-emerald-500/20 cursor-pointer text-white/30 hover:text-emerald-400 transition-colors" title="Activate"><Unlock size={13} /></button>}
                            </div>
                          </td>
                        </motion.tr>
                      ))}
                    </AnimatePresence>
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* EVENTS */}
          {tab === "events" && (
            <div>
              <div className="flex items-center justify-between mb-5">
                <h3 className="font-bold text-white text-lg">County Events <span className="text-white/30 font-normal text-base">({events.length})</span></h3>
                <Btn variant="primary" size="sm" onClick={() => setShowAddEvent(true)}><Plus size={15} /> Add Event</Btn>
              </div>
              <div className="space-y-3">
                <AnimatePresence>
                  {events.map(ev => (
                    <motion.div key={ev.id} initial={{ opacity: 0, x: -16 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 16, height: 0 }}
                      className="bg-[#0D1220] border border-white/8 rounded-2xl p-5 flex items-center gap-4 group hover:border-[#0B1E57]/40 transition-all">
                      <div className="w-12 h-12 bg-[#0B1E57]/20 border border-[#0B1E57]/30 rounded-2xl flex items-center justify-center shrink-0"><Calendar size={20} className="text-blue-400" /></div>
                      <div className="flex-1 min-w-0">
                        <div className="font-bold text-white">{ev.name}</div>
                        <div className="flex flex-wrap gap-3 mt-1 text-xs text-white/35">
                          <span className="flex items-center gap-1"><Clock size={10}/> {ev.date}</span>
                          <span className="flex items-center gap-1"><MapPin size={10}/> {ev.venue}</span>
                          <span className="flex items-center gap-1"><Store size={10}/> {ev.booths} booths</span>
                        </div>
                      </div>
                      <StatusBadge status={ev.status} />
                      <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-[#0B1E57]/40 cursor-pointer text-white/30 hover:text-blue-400"><Edit3 size={13} /></button>
                        <button onClick={() => setConfirmDelete({ type:"event", id: ev.id, name: ev.name })} className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-[#901C1E]/30 cursor-pointer text-white/30 hover:text-[#f87171]"><Trash2 size={13} /></button>
                      </div>
                    </motion.div>
                  ))}
                </AnimatePresence>
              </div>
            </div>
          )}

          {/* APPROVALS */}
          {tab === "approvals" && (
            <div>
              <h3 className="font-bold text-white text-lg mb-5">Pending Approvals <span className="text-white/30 font-normal text-base">({approvalQueue.length})</span></h3>
              {approvalQueue.length === 0 ? (
                <div className="text-center py-16 text-white/30"><CheckCircle size={40} className="mx-auto mb-3 text-emerald-400" /><div className="font-semibold">All caught up — no pending approvals</div></div>
              ) : (
                <div className="space-y-3">
                  <AnimatePresence>
                    {approvalQueue.map(a => (
                      <motion.div key={a.id} initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, x: 100, height: 0 }}
                        className="bg-[#0D1220] border border-[#FFCD05]/20 rounded-2xl p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-2xl overflow-hidden bg-[#141B2E] shrink-0"><img src={u(a.img, 96, 96)} alt={a.name} className="w-full h-full object-cover" /></div>
                        <div className="flex-1 min-w-0">
                          <div className="font-bold text-white">{a.name}</div>
                          <div className="flex flex-wrap gap-3 mt-1 text-xs text-white/35">
                            <span className="flex items-center gap-1"><MapPin size={10}/>{a.county}</span>
                            <span className="flex items-center gap-1"><Tag size={10}/>{a.sector}</span>
                            <span className="flex items-center gap-1"><Clock size={10}/>{a.submitted}</span>
                          </div>
                        </div>
                        <div className="flex gap-2 shrink-0">
                          <Btn variant="primary" size="sm" onClick={() => approveExhibitor(a.id)}><CheckCircle size={13}/> Approve</Btn>
                          <Btn variant="dark" size="sm" onClick={() => setApprovalQueue(q => q.filter(x => x.id !== a.id))}><X size={13}/> Reject</Btn>
                        </div>
                      </motion.div>
                    ))}
                  </AnimatePresence>
                </div>
              )}
            </div>
          )}

          {/* PROFILE */}
          {tab === "profile" && (
            <div className="grid lg:grid-cols-2 gap-6">
              <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 className="font-bold text-white mb-5">County Profile</h3>
                <ImageEditor label="County Banner" hint="Landscape photo of the county · min 1400×600px" />
                <EditField label="County Name" defaultValue="Nairobi County" />
                <EditField label="Governor's Name" defaultValue="H.E. Johnson Sakaja" />
                <EditField label="County Tagline" defaultValue="Kenya's Capital City — Innovation Hub of East Africa" />
                <EditField label="County Description" defaultValue="Nairobi County is Kenya's capital and largest city, home to the nation's financial, commercial and diplomatic centre." multiline />
              </div>
              <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 className="font-bold text-white mb-5">Contact & Stats</h3>
                <EditField label="Official Website" defaultValue="https://nairobi.go.ke" />
                <EditField label="County Email" defaultValue="info@nairobi.go.ke" />
                <EditField label="County Phone" defaultValue="+254 20 222 2222" />
                <EditField label="Population (2024 Census)" defaultValue="4,397,073" />
                <EditField label="Area (km²)" defaultValue="695.1" />
              </div>
            </div>
          )}

          {/* ANALYTICS */}
          {tab === "analytics" && (
            <div className="grid lg:grid-cols-2 gap-6">
              <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 className="font-bold text-white mb-4">Exhibitor Growth</h3>
                <ResponsiveContainer width="100%" height={220}>
                  <BarChart data={chartRevenue}><CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.04)"/><XAxis dataKey="m" stroke="rgba(255,255,255,0.2)" fontSize={11}/><YAxis stroke="rgba(255,255,255,0.2)" fontSize={11}/><Tooltip contentStyle={{ background:"#141B2E", border:"1px solid rgba(255,255,255,0.08)", borderRadius:12, color:"#fff" }}/><Bar dataKey="v" fill="#0B1E57" radius={[6,6,0,0]}/></BarChart>
                </ResponsiveContainer>
              </div>
              <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 className="font-bold text-white mb-4">Sector Breakdown</h3>
                <div className="space-y-3">
                  {sectors.map((s,i) => (
                    <div key={s.id} className="flex items-center gap-3">
                      <div className="text-lg w-7 shrink-0">{s.icon}</div>
                      <div className="flex-1 min-w-0">
                        <div className="text-xs font-semibold text-white/70 mb-1">{s.name}</div>
                        <div className="h-1.5 bg-white/5 rounded-full overflow-hidden"><div className="h-full rounded-full" style={{ width: `${Math.min(100, (s.entities / 50) * 100)}%`, background: PIE_COLS[i % PIE_COLS.length] }} /></div>
                      </div>
                      <span className="text-xs font-bold text-white/40 w-5 text-right shrink-0">{s.entities}</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}
        </motion.div>
      </AnimatePresence>

      {/* Modals */}
      <AnimatePresence>
        {showAddSector && (
          <Modal title="Add New Sector" onClose={() => setShowAddSector(false)}>
            <FormField label="Sector Name" placeholder="e.g. Renewable Energy" value={newSector.name} onChange={v => setNewSector(s => ({ ...s, name: v }))} />
            <div className="mb-4">
              <label className="text-[10px] font-bold text-white/35 uppercase tracking-wider block mb-1.5">Sector Icon (emoji)</label>
              <div className="flex flex-wrap gap-2">
                {["🌾","🏨","🎨","🏥","💻","⚡","🌿","🏗️","🐘","🐟","🏆","🔬","🏛️","🌊","🦁"].map(em => (
                  <button key={em} onClick={() => setNewSector(s => ({ ...s, icon: em }))}
                    className={`w-10 h-10 rounded-xl text-xl flex items-center justify-center cursor-pointer border-2 transition-all ${newSector.icon === em ? "border-[#FFCD05] bg-[#FFCD05]/10" : "border-white/10 hover:border-white/30"}`}>{em}</button>
                ))}
              </div>
            </div>
            <div className="mb-2 text-[10px] font-bold text-white/35 uppercase tracking-wider">Custom Fields (up to 4)</div>
            {["field1","field2","field3","field4"].map((f, i) => (
              <FormField key={f} label={`Field ${i+1}`} placeholder={`e.g. ${["Certification","Annual Output","Market Reach","Employees"][i]}`} value={(newSector as any)[f]} onChange={v => setNewSector(s => ({ ...s, [f]: v }))} />
            ))}
            <div className="flex gap-3 mt-2">
              <Btn variant="primary" className="flex-1" onClick={addSector}>Add Sector</Btn>
              <Btn variant="dark" onClick={() => setShowAddSector(false)}>Cancel</Btn>
            </div>
          </Modal>
        )}
        {showAddExhibitor && (
          <Modal title="Add Exhibitor" onClose={() => setShowAddExhibitor(false)}>
            <FormField label="Business Name" placeholder="e.g. Acacia Honey Farms" value={newEx.name} onChange={v => setNewEx(e => ({ ...e, name: v }))} />
            <FormSelect label="Sector" value={newEx.sector} onChange={v => setNewEx(e => ({ ...e, sector: v }))} options={sectors.map(s => s.name)} />
            <FormSelect label="Plan" value={newEx.plan} onChange={v => setNewEx(e => ({ ...e, plan: v }))} options={["Basic","Silver","Gold"]} />
            <FormField label="Contact Email" placeholder="contact@business.co.ke" value={newEx.contact} onChange={v => setNewEx(e => ({ ...e, contact: v }))} />
            <div className="flex gap-3 mt-2">
              <Btn variant="primary" className="flex-1" onClick={addExhibitor}>Add Exhibitor</Btn>
              <Btn variant="dark" onClick={() => setShowAddExhibitor(false)}>Cancel</Btn>
            </div>
          </Modal>
        )}
        {showAddEvent && (
          <Modal title="Add County Event" onClose={() => setShowAddEvent(false)}>
            <FormField label="Event Name" placeholder="e.g. County Innovation Day" value={newEv.name} onChange={v => setNewEv(e => ({ ...e, name: v }))} />
            <FormField label="Date(s)" placeholder="e.g. 15–17 Sep 2026" value={newEv.date} onChange={v => setNewEv(e => ({ ...e, date: v }))} />
            <FormField label="Venue" placeholder="e.g. County Grounds, Nairobi" value={newEv.venue} onChange={v => setNewEv(e => ({ ...e, venue: v }))} />
            <FormField label="Number of Booths" placeholder="e.g. 50" value={newEv.booths} onChange={v => setNewEv(e => ({ ...e, booths: v }))} type="number" />
            <div className="flex gap-3 mt-2">
              <Btn variant="primary" className="flex-1" onClick={addEvent}>Create Event</Btn>
              <Btn variant="dark" onClick={() => setShowAddEvent(false)}>Cancel</Btn>
            </div>
          </Modal>
        )}
        {confirmDelete && (
          <Modal title="Confirm Delete" onClose={() => setConfirmDelete(null)}>
            <div className="text-center py-4">
              <div className="w-14 h-14 bg-[#901C1E]/15 border border-[#901C1E]/30 rounded-full flex items-center justify-center mx-auto mb-4"><Trash2 size={24} className="text-[#f87171]" /></div>
              <p className="text-white/60 text-sm">Are you sure you want to remove <span className="text-white font-bold">"{confirmDelete.name}"</span>? This action cannot be undone.</p>
            </div>
            <div className="flex gap-3 mt-4">
              <Btn variant="primary" className="flex-1 !bg-[#901C1E] border-[#901C1E]" onClick={deleteItem}>Delete</Btn>
              <Btn variant="dark" onClick={() => setConfirmDelete(null)}>Cancel</Btn>
            </div>
          </Modal>
        )}
      </AnimatePresence>
    </DashShell>
  );
}

// ─── KICC SUPER-ADMIN DASHBOARD ───────────────────────────────────────────────
export function DashAdmin({ navigate }: { navigate: (p: Page) => void }) {
  const [tab, setTab] = useState("overview");
  const [adminContext, setAdminContext] = useState<"kicc"|"county"|"national"|"private">("kicc");

  const [approvalQueue, setApprovalQueue] = useState(INIT_APPROVAL_QUEUE);
  const [countyAdmins, setCountyAdmins]   = useState(INIT_COUNTY_ADMINS);
  const [nationalEntities, setNational]   = useState(INIT_NATIONAL_ENTITIES);
  const [privateEntities, setPrivate]     = useState(INIT_PRIVATE_ENTITIES);
  const [platformContent, setPlatform]    = useState(PLATFORM_CONTENT);
  const [venues, setVenues]               = useState(VENUES as any[]);

  // Modals
  const [showAddCountyAdmin, setShowAddCountyAdmin]     = useState(false);
  const [showAddNational, setShowAddNational]           = useState(false);
  const [showAddPrivate, setShowAddPrivate]             = useState(false);
  const [confirmDelete, setConfirmDelete]               = useState<{ type: string; id: string; name: string } | null>(null);

  const [newCA, setNewCA] = useState({ county: "", name: "", role: "County Director", email: "" });
  const [newNat, setNewNat] = useState({ name: "", ministry: "", type: "Government", contact: "" });
  const [newPrv, setNewPrv] = useState({ name: "", sector: "", type: "Corporate", contact: "" });

  const tabs = ["overview","platform","county-admins","national","private","approvals","venues","analytics"];
  const tabLabels = ["Overview","Platform Content","County Admins","National Gov.","Private Entities","Approvals","Venues","Analytics"];
  const tabIcons = [LayoutDashboard, Settings, Flag, Landmark, Building, CheckCircle, MapPin, BarChart3];

  const addCountyAdmin = () => {
    if (!newCA.county || !newCA.name) return;
    setCountyAdmins(c => [...c, { id: `ca${Date.now()}`, county: newCA.county, name: newCA.name, role: newCA.role, email: newCA.email, status: "active", img: "1611432579402-7037e3e2c1e4", exhibitors: 0, joinDate: "Jul 2026" }]);
    setNewCA({ county: "", name: "", role: "County Director", email: "" });
    setShowAddCountyAdmin(false);
  };

  const addNational = () => {
    if (!newNat.name) return;
    setNational(n => [...n, { id: `n${Date.now()}`, name: newNat.name, ministry: newNat.ministry, type: newNat.type, status: "active", contact: newNat.contact, exhibitors: 0, img: "1611144727915-ef30a08aaeb3" }]);
    setNewNat({ name: "", ministry: "", type: "Government", contact: "" });
    setShowAddNational(false);
  };

  const addPrivate = () => {
    if (!newPrv.name) return;
    setPrivate(p => [...p, { id: `pr${Date.now()}`, name: newPrv.name, sector: newPrv.sector, type: newPrv.type, status: "pending", contact: newPrv.contact, revenue: "—", img: "1741991110666-88115e724741" }]);
    setNewPrv({ name: "", sector: "", type: "Corporate", contact: "" });
    setShowAddPrivate(false);
  };

  const deleteItem = () => {
    if (!confirmDelete) return;
    if (confirmDelete.type === "county-admin") setCountyAdmins(c => c.filter(x => x.id !== confirmDelete.id));
    if (confirmDelete.type === "national")     setNational(n => n.filter(x => x.id !== confirmDelete.id));
    if (confirmDelete.type === "private")      setPrivate(p => p.filter(x => x.id !== confirmDelete.id));
    setConfirmDelete(null);
  };

  const toggleStatus = (type: string, id: string) => {
    const toggle = (arr: any[]) => arr.map(x => x.id === id ? { ...x, status: x.status === "active" ? "suspended" : "active" } : x);
    if (type === "county-admin") setCountyAdmins(toggle);
    if (type === "national") setNational(toggle);
    if (type === "private") setPrivate(toggle);
  };

  const approveItem = (id: string) => setApprovalQueue(q => q.filter(a => a.id !== id));
  const rejectItem  = (id: string) => setApprovalQueue(q => q.filter(a => a.id !== id));

  return (
    <DashShell title="KICC Super-Admin" role="Platform Administrator · All Access" initials="KA" accentColor="#FFCD05" navigate={navigate}
      navItems={tabs.map((t, i) => ({ icon: tabIcons[i], label: tabLabels[i], active: tab === t, onClick: () => setTab(t) }))}>

      {/* Admin context switcher */}
      <div className="flex gap-2 mb-6 flex-wrap">
        {([["kicc","KICC Platform","#FFCD05"],["county","County Admin View","#0B1E57"],["national","National Government","#2D6A4F"],["private","Private Entities","#901C1E"]] as const).map(([ctx, label, color]) => (
          <button key={ctx} onClick={() => setAdminContext(ctx)}
            className={`flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold cursor-pointer border-2 transition-all ${adminContext === ctx ? "text-white" : "text-white/40 border-white/10 hover:border-white/25 hover:text-white"}`}
            style={adminContext === ctx ? { borderColor: color, background: color + "22", color: "#fff" } : {}}>
            {ctx === "kicc" ? <Crown size={13} /> : ctx === "county" ? <Flag size={13} /> : ctx === "national" ? <Landmark size={13} /> : <Building size={13} />}
            {label}
          </button>
        ))}
      </div>

      {adminContext !== "kicc" && (
        <div className={`mb-6 rounded-xl border px-4 py-3 flex items-center gap-3 text-sm`}
          style={{ borderColor: adminContext==="county"?"#0B1E57":adminContext==="national"?"#2D6A4F":"#901C1E", background: (adminContext==="county"?"#0B1E57":adminContext==="national"?"#2D6A4F":"#901C1E")+"18" }}>
          <Shield size={15} className="text-white/60 shrink-0" />
          <span className="text-white/60">You are viewing as <strong className="text-white">{adminContext === "county" ? "County Admin" : adminContext === "national" ? "National Government" : "Private Entity Admin"}</strong> — KICC privileges still apply. <button onClick={() => setAdminContext("kicc")} className="text-[#FFCD05] underline cursor-pointer ml-1">Switch back to KICC</button></span>
        </div>
      )}

      <AnimatePresence mode="wait">
        <motion.div key={tab + adminContext} initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -6 }} transition={{ duration: 0.22 }}>

          {/* OVERVIEW */}
          {tab === "overview" && (
            <>
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <KpiCard label="Total Exhibitors" value="1,847" change="+12% MoM" icon={Store} accent="#FFCD05" />
                <KpiCard label="Active County Admins" value={String(countyAdmins.filter(c => c.status === "active").length)} icon={Users} />
                <KpiCard label="Pending Approvals" value={String(approvalQueue.length)} icon={AlertCircle} accent="#901C1E" />
                <KpiCard label="Platform Revenue" value="KES 4.2M" change="+18%" icon={DollarSign} accent="#2D6A4F" />
              </div>

              {/* Hierarchy card */}
              <div className="bg-[#0D1220] border border-[#FFCD05]/20 rounded-2xl p-6 mb-6">
                <div className="flex items-center gap-3 mb-5">
                  <Crown size={18} className="text-[#FFCD05]" />
                  <h3 className="font-bold text-white">Admin Hierarchy — Access Control</h3>
                </div>
                <div className="flex flex-col md:flex-row items-center gap-4">
                  {[
                    { label: "KICC Platform Admin", sub: "Full access · Supersedes all", color: "#FFCD05", count: 3, icon: Crown },
                    { label: "National Gov. Entities", sub: "Ministries & Parastatals", color: "#2D6A4F", count: nationalEntities.length, icon: Landmark },
                    { label: "County Admins", sub: "47 county administrators", color: "#0B1E57", count: countyAdmins.length, icon: Flag },
                    { label: "Private Entities", sub: "Corporates & Exhibitors", color: "#901C1E", count: privateEntities.length, icon: Building },
                  ].map((tier, i) => (
                    <div key={tier.label} className="flex items-center gap-3 w-full md:w-auto">
                      <div className="flex-1 md:flex-none bg-[#141B2E] border rounded-xl p-4 text-center min-w-[140px]" style={{ borderColor: tier.color + "40" }}>
                        <tier.icon size={20} className="mx-auto mb-2" style={{ color: tier.color }} />
                        <div className="font-black text-white text-lg">{tier.count}</div>
                        <div className="font-bold text-white text-xs">{tier.label}</div>
                        <div className="text-white/30 text-[10px] mt-0.5">{tier.sub}</div>
                      </div>
                      {i < 3 && <ChevronRight size={18} className="text-white/20 shrink-0 hidden md:block" />}
                    </div>
                  ))}
                </div>
              </div>

              <div className="grid lg:grid-cols-2 gap-6">
                <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                  <h3 className="font-bold text-white mb-4">Platform Revenue (KES '000)</h3>
                  <ResponsiveContainer width="100%" height={200}>
                    <AreaChart data={chartRevenue}><defs><linearGradient id="ag" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#FFCD05" stopOpacity={0.3}/><stop offset="95%" stopColor="#FFCD05" stopOpacity={0}/></linearGradient></defs><CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.04)"/><XAxis dataKey="m" stroke="rgba(255,255,255,0.2)" fontSize={11}/><YAxis stroke="rgba(255,255,255,0.2)" fontSize={11}/><Tooltip contentStyle={{ background:"#141B2E", border:"1px solid rgba(255,255,255,0.08)", borderRadius:12, color:"#fff" }}/><Area type="monotone" dataKey="v" stroke="#FFCD05" fill="url(#ag)" strokeWidth={2}/></AreaChart>
                  </ResponsiveContainer>
                </div>
                <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                  <h3 className="font-bold text-white mb-4">Revenue Split</h3>
                  <ResponsiveContainer width="100%" height={200}>
                    <PieChart><Pie data={PIE_DATA} cx="50%" cy="50%" outerRadius={80} dataKey="v" label={({ name }) => name} labelLine={false}>
                      {PIE_DATA.map((_,i) => <Cell key={i} fill={PIE_COLS[i % PIE_COLS.length]} />)}
                    </Pie><Tooltip contentStyle={{ background:"#141B2E", border:"1px solid rgba(255,255,255,0.08)", borderRadius:12, color:"#fff" }}/></PieChart>
                  </ResponsiveContainer>
                </div>
              </div>
            </>
          )}

          {/* PLATFORM CONTENT */}
          {tab === "platform" && (
            <div className="grid lg:grid-cols-2 gap-6">
              <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 className="font-bold text-white mb-5">Homepage Content</h3>
                <ImageEditor label="Hero Background Image" hint="Full-width landscape · min 1600×900px" />
                <EditField label="Hero Title" defaultValue={platformContent.heroTitle} />
                <EditField label="Hero Subtitle" defaultValue={platformContent.heroSubtitle} multiline />
                <div className="grid grid-cols-2 gap-3 mt-4">
                  {["stat1","stat2","stat3","stat4"].map(k => (
                    <div key={k}>
                      <EditField label={`${k.replace("stat","Stat ")} Label`} defaultValue={(platformContent as any)[`${k}Label`]} />
                      <EditField label={`${k.replace("stat","Stat ")} Value`} defaultValue={(platformContent as any)[`${k}Value`]} />
                    </div>
                  ))}
                </div>
              </div>
              <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <h3 className="font-bold text-white mb-5">Commission Settings</h3>
                <div className="space-y-4">
                  {[["KICC Platform Commission %","commissionKICC"],["County Admin Commission %","commissionCounty"],["Seller Payout %","commissionSeller"]].map(([l,k]) => (
                    <div key={k}>
                      <label className="text-[10px] font-bold text-white/35 uppercase tracking-wider block mb-1.5">{l}</label>
                      <div className="flex items-center gap-3">
                        <input type="range" min="0" max="100" defaultValue={(platformContent as any)[k]}
                          className="flex-1 accent-[#FFCD05]" onChange={e => setPlatform(p => ({ ...p, [k]: e.target.value }))} />
                        <span className="text-[#FFCD05] font-black text-lg w-12 text-right">{(platformContent as any)[k]}%</span>
                      </div>
                    </div>
                  ))}
                </div>
                <div className="mt-6 p-4 bg-[#141B2E] rounded-xl border border-white/8">
                  <div className="text-xs font-bold text-white/35 uppercase tracking-wider mb-3">Global Feature Toggles</div>
                  {[["Live Streaming",true],["Digital Marketplace",true],["Exhibitor Directory",true],["County Profiles",true],["Screen Advertising",false]].map(([f, on]) => (
                    <div key={f as string} className="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                      <span className="text-sm text-white/60">{f as string}</span>
                      {on ? <ToggleRight size={28} className="text-[#FFCD05] cursor-pointer" /> : <ToggleLeft size={28} className="text-white/20 cursor-pointer" />}
                    </div>
                  ))}
                </div>
                <Btn variant="gold" className="w-full mt-5">Save All Settings</Btn>
              </div>
            </div>
          )}

          {/* COUNTY ADMINS */}
          {tab === "county-admins" && (
            <div>
              <div className="flex items-center justify-between mb-5">
                <h3 className="font-bold text-white text-lg">County Admin Accounts <span className="text-white/30 font-normal text-base">({countyAdmins.length})</span></h3>
                <Btn variant="primary" size="sm" onClick={() => setShowAddCountyAdmin(true)}><Plus size={15} /> Add County Admin</Btn>
              </div>
              <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <AnimatePresence>
                  {countyAdmins.map(ca => (
                    <motion.div key={ca.id} initial={{ opacity: 0, scale: 0.92 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.88 }}
                      className="bg-[#0D1220] border border-white/8 hover:border-[#FFCD05]/30 rounded-2xl p-5 group transition-all">
                      <div className="flex items-start justify-between mb-4">
                        <div className="w-12 h-12 rounded-2xl overflow-hidden bg-[#141B2E]"><img src={u(ca.img, 96, 96)} alt={ca.name} className="w-full h-full object-cover" /></div>
                        <StatusBadge status={ca.status} />
                      </div>
                      <div className="font-bold text-white">{ca.name}</div>
                      <div className="text-xs text-[#FFCD05] font-semibold">{ca.county} County</div>
                      <div className="text-xs text-white/35 mt-0.5">{ca.role}</div>
                      <div className="text-xs text-white/25 mt-1 truncate">{ca.email}</div>
                      <div className="mt-3 flex items-center gap-2 text-xs text-white/30">
                        <Store size={10} /><span>{ca.exhibitors} exhibitors</span>
                        <span className="ml-auto">{ca.joinDate}</span>
                      </div>
                      <div className="flex gap-2 mt-4 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onClick={() => toggleStatus("county-admin", ca.id)} className={`flex-1 py-1.5 rounded-lg text-[10px] font-bold cursor-pointer border transition-all ${ca.status==="active"?"border-orange-500/30 text-orange-400 hover:bg-orange-500/10":"border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10"}`}>
                          {ca.status === "active" ? "Suspend" : "Reactivate"}
                        </button>
                        <button onClick={() => setConfirmDelete({ type:"county-admin", id: ca.id, name: `${ca.name} (${ca.county})` })} className="w-8 h-8 flex items-center justify-center rounded-lg border border-[#901C1E]/30 text-[#f87171] hover:bg-[#901C1E]/20 cursor-pointer transition-all"><Trash2 size={12} /></button>
                      </div>
                    </motion.div>
                  ))}
                </AnimatePresence>
              </div>
            </div>
          )}

          {/* NATIONAL GOVERNMENT */}
          {tab === "national" && (
            <div>
              <div className="flex items-center justify-between mb-5">
                <h3 className="font-bold text-white text-lg">National Government Entities <span className="text-white/30 font-normal text-base">({nationalEntities.length})</span></h3>
                <Btn variant="primary" size="sm" onClick={() => setShowAddNational(true)}><Plus size={15} /> Add Entity</Btn>
              </div>
              <div className="overflow-hidden rounded-2xl border border-white/8">
                <table className="w-full">
                  <thead><tr className="bg-[#141B2E]">{["Logo","Name","Ministry","Type","Status","Contact","Actions"].map(h => <th key={h} className="text-[10px] font-bold text-white/30 uppercase tracking-wider px-4 py-3 text-left">{h}</th>)}</tr></thead>
                  <tbody>
                    <AnimatePresence>
                      {nationalEntities.map(n => (
                        <motion.tr key={n.id} initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}
                          className="border-b border-white/5 last:border-0 hover:bg-white/2 transition-colors group">
                          <td className="px-4 py-3"><div className="w-9 h-9 rounded-xl overflow-hidden bg-[#141B2E]"><img src={u(n.img, 72, 72)} alt="" className="w-full h-full object-cover" /></div></td>
                          <td className="px-4 py-3 font-semibold text-white text-sm">{n.name}</td>
                          <td className="px-4 py-3 text-sm text-white/50">{n.ministry}</td>
                          <td className="px-4 py-3"><span className="text-[10px] font-bold px-2 py-1 bg-[#2D6A4F]/15 text-emerald-400 border border-emerald-500/25 rounded-full">{n.type}</span></td>
                          <td className="px-4 py-3"><StatusBadge status={n.status} /></td>
                          <td className="px-4 py-3 text-xs text-white/30 truncate max-w-[140px]">{n.contact}</td>
                          <td className="px-4 py-3">
                            <div className="flex gap-1">
                              <button className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-white/8 cursor-pointer text-white/30 hover:text-white"><Edit3 size={13} /></button>
                              <button onClick={() => toggleStatus("national", n.id)} className={`w-7 h-7 flex items-center justify-center rounded-lg cursor-pointer transition-colors ${n.status==="active"?"hover:bg-orange-500/20 text-white/30 hover:text-orange-400":"hover:bg-emerald-500/20 text-white/30 hover:text-emerald-400"}`}>{n.status==="active"?<Ban size={13}/>:<Unlock size={13}/>}</button>
                              <button onClick={() => setConfirmDelete({ type:"national", id: n.id, name: n.name })} className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-[#901C1E]/30 cursor-pointer text-white/30 hover:text-[#f87171]"><Trash2 size={13} /></button>
                            </div>
                          </td>
                        </motion.tr>
                      ))}
                    </AnimatePresence>
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* PRIVATE ENTITIES */}
          {tab === "private" && (
            <div>
              <div className="flex items-center justify-between mb-5">
                <h3 className="font-bold text-white text-lg">Private Entities <span className="text-white/30 font-normal text-base">({privateEntities.length})</span></h3>
                <Btn variant="primary" size="sm" onClick={() => setShowAddPrivate(true)}><Plus size={15} /> Add Entity</Btn>
              </div>
              <div className="grid sm:grid-cols-2 gap-4">
                <AnimatePresence>
                  {privateEntities.map(p => (
                    <motion.div key={p.id} initial={{ opacity: 0, scale: 0.92 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.88 }}
                      className="bg-[#0D1220] border border-white/8 hover:border-[#901C1E]/30 rounded-2xl p-5 group transition-all">
                      <div className="flex items-start justify-between mb-4">
                        <div className="w-12 h-12 rounded-2xl overflow-hidden bg-[#141B2E]"><img src={u(p.img, 96, 96)} alt={p.name} className="w-full h-full object-cover" /></div>
                        <StatusBadge status={p.status} />
                      </div>
                      <div className="font-bold text-white">{p.name}</div>
                      <div className="flex gap-2 mt-1"><span className="text-xs text-white/40">{p.sector}</span><span className="text-xs text-white/20">·</span><span className="text-xs text-white/40">{p.type}</span></div>
                      <div className="text-xs text-white/25 mt-1 truncate">{p.contact}</div>
                      <div className="flex items-center justify-between mt-3">
                        <span className="text-sm font-bold text-[#FFCD05]">{p.revenue}</span>
                        <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                          <button onClick={() => toggleStatus("private", p.id)} className={`w-7 h-7 flex items-center justify-center rounded-lg cursor-pointer ${p.status==="active"?"hover:bg-orange-500/20 text-white/30 hover:text-orange-400":"hover:bg-emerald-500/20 text-white/30 hover:text-emerald-400"}`}>{p.status==="active"?<Ban size={13}/>:<Unlock size={13}/>}</button>
                          <button onClick={() => setConfirmDelete({ type:"private", id: p.id, name: p.name })} className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-[#901C1E]/30 cursor-pointer text-white/30 hover:text-[#f87171]"><Trash2 size={13} /></button>
                        </div>
                      </div>
                    </motion.div>
                  ))}
                </AnimatePresence>
              </div>
            </div>
          )}

          {/* APPROVALS */}
          {tab === "approvals" && (
            <div>
              <h3 className="font-bold text-white text-lg mb-5">Global Approval Queue <span className="text-white/30 font-normal text-base">({approvalQueue.length})</span></h3>
              {approvalQueue.length === 0 ? (
                <div className="text-center py-16 text-white/30"><CheckCircle size={40} className="mx-auto mb-3 text-emerald-400" /><div className="font-semibold">All caught up — no pending approvals</div></div>
              ) : (
                <div className="space-y-3">
                  <AnimatePresence>
                    {approvalQueue.map(a => (
                      <motion.div key={a.id} initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, x: 100, height: 0 }}
                        className="bg-[#0D1220] border border-[#FFCD05]/20 rounded-2xl p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-2xl overflow-hidden bg-[#141B2E] shrink-0"><img src={u(a.img, 96, 96)} alt={a.name} className="w-full h-full object-cover" /></div>
                        <div className="flex-1 min-w-0">
                          <div className="font-bold text-white">{a.name}</div>
                          <div className="flex flex-wrap gap-3 mt-1 text-xs text-white/35">
                            <span className="flex items-center gap-1"><MapPin size={10}/>{a.county}</span>
                            <span className="flex items-center gap-1"><Tag size={10}/>{a.sector}</span>
                            <span className="flex items-center gap-1"><Clock size={10}/>{a.submitted}</span>
                            <span className="flex items-center gap-1"><FileText size={10}/>{a.type}</span>
                          </div>
                        </div>
                        <div className="flex gap-2 shrink-0 flex-wrap">
                          <Btn variant="primary" size="sm" onClick={() => approveItem(a.id)}><CheckCircle size={13}/> Approve</Btn>
                          <Btn variant="dark" size="sm" onClick={() => rejectItem(a.id)}><X size={13}/> Reject</Btn>
                        </div>
                      </motion.div>
                    ))}
                  </AnimatePresence>
                </div>
              )}
            </div>
          )}

          {/* VENUES */}
          {tab === "venues" && (
            <div>
              <h3 className="font-bold text-white text-lg mb-5">KICC Venue Management</h3>
              <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {venues.map(v => (
                  <div key={v.id} className="bg-[#0D1220] border border-white/8 rounded-2xl overflow-hidden group hover:border-[#FFCD05]/30 transition-all">
                    <div className="h-36 overflow-hidden bg-[#141B2E]">
                      <img src={u(v.fallback || v.img || "1775314054195-85f31de0c944", 400, 250)} alt={v.name} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                    </div>
                    <div className="p-4">
                      <div className="font-bold text-white text-sm">{v.name}</div>
                      <div className="text-white/35 text-xs mt-1">{v.capacity} capacity · {v.area}</div>
                      <div className="flex items-center justify-between mt-3">
                        <span className="text-[#FFCD05] font-bold text-sm">{v.priceDay}</span>
                        <div className="flex gap-1">
                          <button className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-white/8 cursor-pointer text-white/30 hover:text-white"><Edit3 size={12}/></button>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* ANALYTICS */}
          {tab === "analytics" && (
            <>
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <KpiCard label="Platform page views" value="124K" change="+22%" icon={Eye} accent="#FFCD05" />
                <KpiCard label="New registrations" value="847" change="+15%" icon={UserCheck} />
                <KpiCard label="Marketplace orders" value="2,341" change="+31%" icon={ShoppingCart} accent="#2D6A4F" />
                <KpiCard label="Live stream sessions" value="89" change="+67%" icon={Tv} accent="#901C1E" />
              </div>
              <div className="grid lg:grid-cols-2 gap-6">
                <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                  <h3 className="font-bold text-white mb-4">Revenue by Month (KES M)</h3>
                  <ResponsiveContainer width="100%" height={220}>
                    <BarChart data={chartRevenue}><CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.04)"/><XAxis dataKey="m" stroke="rgba(255,255,255,0.2)" fontSize={11}/><YAxis stroke="rgba(255,255,255,0.2)" fontSize={11}/><Tooltip contentStyle={{ background:"#141B2E", border:"1px solid rgba(255,255,255,0.08)", borderRadius:12, color:"#fff" }}/><Bar dataKey="v" radius={[6,6,0,0]} fill="#FFCD05"/></BarChart>
                  </ResponsiveContainer>
                </div>
                <div className="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                  <h3 className="font-bold text-white mb-4">Revenue Sources</h3>
                  <ResponsiveContainer width="100%" height={220}>
                    <PieChart><Pie data={PIE_DATA} cx="50%" cy="50%" outerRadius={80} dataKey="v" label={({ name, percent }) => `${name} ${(percent*100).toFixed(0)}%`} labelLine={false}>
                      {PIE_DATA.map((_,i) => <Cell key={i} fill={PIE_COLS[i % PIE_COLS.length]} />)}
                    </Pie><Tooltip contentStyle={{ background:"#141B2E", border:"1px solid rgba(255,255,255,0.08)", borderRadius:12, color:"#fff" }}/></PieChart>
                  </ResponsiveContainer>
                </div>
              </div>
            </>
          )}
        </motion.div>
      </AnimatePresence>

      {/* MODALS */}
      <AnimatePresence>
        {showAddCountyAdmin && (
          <Modal title="Add County Admin" onClose={() => setShowAddCountyAdmin(false)}>
            <FormSelect label="County" value={newCA.county} onChange={v => setNewCA(c => ({ ...c, county: v }))} options={COUNTIES.map(c => c.name)} />
            <FormField label="Admin Full Name" placeholder="e.g. Jane Wanjiku" value={newCA.name} onChange={v => setNewCA(c => ({ ...c, name: v }))} />
            <FormSelect label="Role" value={newCA.role} onChange={v => setNewCA(c => ({ ...c, role: v }))} options={["County Director","County Coordinator","Deputy Director"]} />
            <FormField label="Official Email" placeholder="name@county.go.ke" value={newCA.email} onChange={v => setNewCA(c => ({ ...c, email: v }))} />
            <div className="flex gap-3 mt-2">
              <Btn variant="primary" className="flex-1" onClick={addCountyAdmin}>Create Account</Btn>
              <Btn variant="dark" onClick={() => setShowAddCountyAdmin(false)}>Cancel</Btn>
            </div>
          </Modal>
        )}
        {showAddNational && (
          <Modal title="Add National Government Entity" onClose={() => setShowAddNational(false)}>
            <FormField label="Entity Name" placeholder="e.g. Kenya Revenue Authority" value={newNat.name} onChange={v => setNewNat(n => ({ ...n, name: v }))} />
            <FormField label="Ministry / Parent Body" placeholder="e.g. National Treasury" value={newNat.ministry} onChange={v => setNewNat(n => ({ ...n, ministry: v }))} />
            <FormSelect label="Entity Type" value={newNat.type} onChange={v => setNewNat(n => ({ ...n, type: v }))} options={["Government","Parastatal","Regulatory","Research","Agency"]} />
            <FormField label="Official Contact Email" placeholder="info@entity.go.ke" value={newNat.contact} onChange={v => setNewNat(n => ({ ...n, contact: v }))} />
            <div className="flex gap-3 mt-2">
              <Btn variant="primary" className="flex-1" onClick={addNational}>Add Entity</Btn>
              <Btn variant="dark" onClick={() => setShowAddNational(false)}>Cancel</Btn>
            </div>
          </Modal>
        )}
        {showAddPrivate && (
          <Modal title="Add Private Entity" onClose={() => setShowAddPrivate(false)}>
            <FormField label="Company Name" placeholder="e.g. Jumia Kenya" value={newPrv.name} onChange={v => setNewPrv(p => ({ ...p, name: v }))} />
            <FormField label="Business Sector" placeholder="e.g. E-Commerce" value={newPrv.sector} onChange={v => setNewPrv(p => ({ ...p, sector: v }))} />
            <FormSelect label="Entity Type" value={newPrv.type} onChange={v => setNewPrv(p => ({ ...p, type: v }))} options={["Corporate","Startup","SME","NGO","Cooperative"]} />
            <FormField label="Contact Email" placeholder="contact@company.co.ke" value={newPrv.contact} onChange={v => setNewPrv(p => ({ ...p, contact: v }))} />
            <div className="flex gap-3 mt-2">
              <Btn variant="primary" className="flex-1" onClick={addPrivate}>Add Entity</Btn>
              <Btn variant="dark" onClick={() => setShowAddPrivate(false)}>Cancel</Btn>
            </div>
          </Modal>
        )}
        {confirmDelete && (
          <Modal title="Confirm Delete" onClose={() => setConfirmDelete(null)}>
            <div className="text-center py-4">
              <div className="w-14 h-14 bg-[#901C1E]/15 border border-[#901C1E]/30 rounded-full flex items-center justify-center mx-auto mb-4"><Trash2 size={24} className="text-[#f87171]" /></div>
              <p className="text-white/60 text-sm">Are you sure you want to permanently remove <span className="text-white font-bold">"{confirmDelete.name}"</span>?</p>
            </div>
            <div className="flex gap-3 mt-4">
              <Btn variant="primary" className="flex-1 !bg-[#901C1E] border-[#901C1E]" onClick={deleteItem}>Delete Permanently</Btn>
              <Btn variant="dark" onClick={() => setConfirmDelete(null)}>Cancel</Btn>
            </div>
          </Modal>
        )}
      </AnimatePresence>
    </DashShell>
  );
}
