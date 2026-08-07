import { useState, useEffect, useRef, useCallback } from "react";
import { motion, AnimatePresence } from "motion/react";
import {
  MapPin, Phone, Mail, Globe, Shield, ShoppingCart, Bell, Menu, X,
  ArrowRight, LogOut, Upload, Image as ImageIcon, Plus, Tag,
  Layers, BarChart3, CreditCard, DollarSign, Users, TrendingUp,
  Package, Eye, CheckCircle, AlertCircle, Clock, Star,
  Edit3, Trash2, Download, FileText, Store, Settings,
  Wifi, QrCode, LayoutDashboard, Briefcase, Sliders,
  ToggleLeft, ToggleRight, ChevronDown, ChevronUp, ChevronRight,
  PlusCircle, Ban, Unlock, UserCheck, Sparkles, ExternalLink,
  MoreHorizontal, Tv, Award, Zap, Calendar
} from "lucide-react";
import {
  AreaChart, Area, BarChart, Bar, XAxis, YAxis, CartesianGrid,
  Tooltip, ResponsiveContainer, PieChart, Pie, Cell,
} from "recharts";
import kiccBg from "../imports/kicc.jpg";
import kiccLogo from "../imports/image.png";

export { kiccBg, kiccLogo };
export {
  AreaChart, Area, BarChart, Bar, XAxis, YAxis, CartesianGrid,
  Tooltip, ResponsiveContainer, PieChart, Pie, Cell,
};
export {
  MapPin, Phone, Mail, Globe, Shield, ShoppingCart, Bell, Menu, X,
  ArrowRight, LogOut, Upload, ImageIcon, Plus, Tag,
  Layers, BarChart3, CreditCard, DollarSign, Users, TrendingUp,
  Package, Eye, CheckCircle, AlertCircle, Clock, Star,
  Edit3, Trash2, Download, FileText, Store, Settings,
  Wifi, QrCode, LayoutDashboard, Briefcase, Sliders,
  ToggleLeft, ToggleRight, ChevronDown, ChevronUp, ChevronRight,
  PlusCircle, Ban, Unlock, UserCheck, Sparkles, ExternalLink,
  MoreHorizontal, Tv, Award, Zap, Calendar
};

// ─── Types ────────────────────────────────────────────────────────────────────
export type Page =
  | "home" | "county" | "sector" | "entity" | "marketplace" | "product"
  | "cart" | "checkout" | "mpesa" | "order-success"
  | "exhibition" | "booth-picker" | "booth-checkout" | "booking-confirmed"
  | "venue" | "hotel-booking" | "screens" | "live"
  | "login" | "otp"
  | "dash-buyer" | "dash-exhibitor" | "dash-seller" | "dash-county" | "dash-admin"
  | "directory" | "exhibitor-public";

// ─── Data ─────────────────────────────────────────────────────────────────────
export const KICC_STATS = [
  { label: "Years of Excellence", value: 52, suffix: "+" },
  { label: "Kenya Counties", value: 47, suffix: "" },
  { label: "Digital Screens", value: 18, suffix: "" },
  { label: "Events per Year", value: 200, suffix: "+" },
];

export const COUNTIES = [
  { id: "nairobi",         name: "Nairobi",         img: "1741991110666-88115e724741", tagline: "Kenya's Capital City",      region: "Central" },
  { id: "mombasa",         name: "Mombasa",         img: "1652511928669-f3ce2797913b", tagline: "The Coastal Hub",           region: "Coast"   },
  { id: "kisumu",          name: "Kisumu",           img: "1751568928581-874900ec53f0", tagline: "Lake Victoria City",        region: "Nyanza"  },
  { id: "nakuru",          name: "Nakuru",           img: "1554490594-0e5b97120099",    tagline: "The Rift Valley Hub",       region: "Rift Valley" },
  { id: "kilifi",          name: "Kilifi",           img: "1664093671757-df1b2a7bb5da", tagline: "Coastal Paradise",          region: "Coast"   },
  { id: "laikipia",        name: "Laikipia",         img: "1709402606682-400133d92ab2", tagline: "Wildlife & Ranches",        region: "Central" },
  { id: "kajiado",         name: "Kajiado",          img: "1517503462743-f87ba95a8cae", tagline: "Maasai Heartland",          region: "Rift Valley" },
  { id: "machakos",        name: "Machakos",         img: "1776409933815-3497439f829a", tagline: "The Apple County",          region: "Eastern" },
  { id: "kiambu",          name: "Kiambu",           img: "1741991109902-98bf764fb35d", tagline: "Nairobi's Neighbour",       region: "Central" },
  { id: "muranga",         name: "Murang'a",         img: "1609848930155-cd505cf3cd38", tagline: "Coffee & Tea Highlands",    region: "Central" },
  { id: "nyeri",           name: "Nyeri",            img: "1741991109886-90e70988f27b", tagline: "Mount Kenya Gateway",       region: "Central" },
  { id: "kirinyaga",       name: "Kirinyaga",        img: "1751568928765-bd4b1499969e", tagline: "Rice & Tea Country",        region: "Central" },
  { id: "nyandarua",       name: "Nyandarua",        img: "1745526220488-6d7b179ac084", tagline: "The Aberdare Range",        region: "Central" },
  { id: "meru",            name: "Meru",             img: "1649808571507-3a396f7f7e6e", tagline: "Mt Kenya Eastern Slope",    region: "Eastern" },
  { id: "tharaka-nithi",   name: "Tharaka-Nithi",   img: "1706394212063-1017e86d5d4b", tagline: "The Heartland County",      region: "Eastern" },
  { id: "embu",            name: "Embu",             img: "1729686689344-2f8bb3f65e5c", tagline: "Tana River Source",         region: "Eastern" },
  { id: "kitui",           name: "Kitui",            img: "1776409933876-022c6cb0baf6", tagline: "The Kyambogo Hills",        region: "Eastern" },
  { id: "makueni",         name: "Makueni",          img: "1773858437375-e49a91b73ff1", tagline: "Wote & Makindu Gateway",    region: "Eastern" },
  { id: "kwale",           name: "Kwale",            img: "1564490292125-2e3c78a0ef44", tagline: "Diani Beach Paradise",      region: "Coast"   },
  { id: "tana-river",      name: "Tana River",       img: "1680346087987-e7898e5349fe", tagline: "The Delta Wilderness",      region: "Coast"   },
  { id: "lamu",            name: "Lamu",             img: "1652511931085-97666ab442ce", tagline: "Ancient Swahili Culture",   region: "Coast"   },
  { id: "taita-taveta",    name: "Taita-Taveta",     img: "1577971132997-c10be9372519", tagline: "Tsavo Wildlife Corridor",   region: "Coast"   },
  { id: "garissa",         name: "Garissa",          img: "1618123305218-c1fa44178634", tagline: "NFD Gateway County",        region: "North Eastern" },
  { id: "wajir",           name: "Wajir",            img: "1582553538004-b01de2cecb34", tagline: "The Horn of Africa Entry",  region: "North Eastern" },
  { id: "mandera",         name: "Mandera",          img: "1517118828960-de5ea37d8ae6", tagline: "Tri-Border County",         region: "North Eastern" },
  { id: "marsabit",        name: "Marsabit",         img: "1710077539513-6d0b9cf273e2", tagline: "Lake Turkana South",        region: "Eastern" },
  { id: "isiolo",          name: "Isiolo",           img: "1674909073723-e74e82810733", tagline: "Northern Kenya Gateway",    region: "Eastern" },
  { id: "turkana",         name: "Turkana",          img: "1710136708839-731de0fe290a", tagline: "Jade Sea County",           region: "Rift Valley" },
  { id: "west-pokot",      name: "West Pokot",       img: "1681166483273-110dcfa6ed08", tagline: "The Pokot Highlands",       region: "Rift Valley" },
  { id: "samburu",         name: "Samburu",          img: "1709403229285-35ed7d88a79b", tagline: "Samburu National Reserve",  region: "Rift Valley" },
  { id: "trans-nzoia",     name: "Trans-Nzoia",      img: "1709403337027-45324f24fae3", tagline: "Kenya's Bread Basket",      region: "Rift Valley" },
  { id: "uasin-gishu",     name: "Uasin Gishu",      img: "1741991109902-98bf764fb35d", tagline: "Athletics Capital",         region: "Rift Valley" },
  { id: "elgeyo-marakwet", name: "Elgeyo-Marakwet",  img: "1609848930155-cd505cf3cd38", tagline: "The Kerio Valley",          region: "Rift Valley" },
  { id: "nandi",           name: "Nandi",            img: "1554490594-0e5b97120099",    tagline: "Tea & Athletics County",    region: "Rift Valley" },
  { id: "baringo",         name: "Baringo",          img: "1710136708839-731de0fe290a", tagline: "Lake Baringo & Bogoria",    region: "Rift Valley" },
  { id: "narok",           name: "Narok",            img: "1517503462743-f87ba95a8cae", tagline: "Maasai Mara Home",          region: "Rift Valley" },
  { id: "kericho",         name: "Kericho",          img: "1609848930155-cd505cf3cd38", tagline: "Tea Capital of Kenya",      region: "Rift Valley" },
  { id: "bomet",           name: "Bomet",            img: "1745526220488-6d7b179ac084", tagline: "Tea & Pyrethrum County",    region: "Rift Valley" },
  { id: "kakamega",        name: "Kakamega",         img: "1609848930155-cd505cf3cd38", tagline: "Kenya's Rainforest",        region: "Western" },
  { id: "vihiga",          name: "Vihiga",           img: "1751568928765-bd4b1499969e", tagline: "The Western Highlands",     region: "Western" },
  { id: "bungoma",         name: "Bungoma",          img: "1745526220488-6d7b179ac084", tagline: "Mt. Elgon County",          region: "Western" },
  { id: "busia",           name: "Busia",            img: "1776409933876-022c6cb0baf6", tagline: "Uganda Border Trade Hub",   region: "Western" },
  { id: "siaya",           name: "Siaya",            img: "1751568928462-e90c4dcae0a4", tagline: "Luo Cultural Heartland",    region: "Nyanza"  },
  { id: "homa-bay",        name: "Homa Bay",         img: "1751568928684-9fa66911be90", tagline: "Lake Victoria Shore",       region: "Nyanza"  },
  { id: "migori",          name: "Migori",           img: "1751568928765-bd4b1499969e", tagline: "Tanzania Border County",    region: "Nyanza"  },
  { id: "kisii",           name: "Kisii",            img: "1783024865247-d775f6b0b41b", tagline: "Soapstone Craft Capital",   region: "Nyanza"  },
  { id: "nyamira",         name: "Nyamira",          img: "1609848930155-cd505cf3cd38", tagline: "Tea & Coffee Hills",        region: "Nyanza"  },
];

export const PRODUCTS = [
  { id: "p1", name: "Nyeri AA Single-Origin Coffee — Light Roast", price: 1200, img: "1773858437375-e49a91b73ff1", category: "Food & Beverages",  county: "Nyeri",   seller: "Mt Kenya Coffee Co.", rating: 4.8, reviews: 142, stock: 48 },
  { id: "p2", name: "Hand-Woven Maasai Shuka — Red & Blue",        price: 3500, img: "1772411535291-aa5884035934", category: "Crafts & Textiles", county: "Kajiado", seller: "Enkiama Crafts",      rating: 4.7, reviews: 98,  stock: 12 },
  { id: "p3", name: "Coastal Coconut & Baobab Body Oil Set",        price: 890,  img: "1776409933815-3497439f829a", category: "Beauty & Wellness", county: "Mombasa", seller: "Coastal Naturals",    rating: 4.9, reviews: 231, stock: 86 },
  { id: "p4", name: "Kikuyu Handmade Soapstone Carving",            price: 2200, img: "1783024865247-d775f6b0b41b", category: "Arts & Décor",      county: "Kisii",   seller: "Heritage Pottery",    rating: 4.6, reviews: 67,  stock: 5  },
  { id: "p5", name: "Maasai Beaded Bracelet Set",                   price: 850,  img: "1776409933876-022c6cb0baf6", category: "Jewellery",         county: "Kajiado", seller: "Enkiama Crafts",      rating: 4.7, reviews: 54,  stock: 30 },
  { id: "p6", name: "Kilifi Fresh Coconut Oil — Cold Pressed",       price: 650,  img: "1564490292125-2e3c78a0ef44", category: "Food & Beverages",  county: "Kilifi",  seller: "Coastal Naturals",    rating: 4.6, reviews: 88,  stock: 60 },
  { id: "p7", name: "Nakuru Honey — Pure Raw Wildflower",            price: 980,  img: "1777065851469-71aef898a26f", category: "Food & Beverages",  county: "Nakuru",  seller: "Rift Valley Farms",   rating: 4.9, reviews: 203, stock: 34 },
  { id: "p8", name: "Kisumu Tilapia Fillet — Fresh Frozen",          price: 750,  img: "1751568928684-9fa66911be90", category: "Food & Beverages",  county: "Kisumu",  seller: "Lake Victoria Fresh", rating: 4.5, reviews: 76,  stock: 22 },
];

export const VENUES = [
  { id: "tsavo",        name: "Tsavo Hall",        capacity: "2,000", area: "4,800m²", priceDay: "KES 250,000", sqm: 4800, img: "https://kicc.co.ke/uploads/2026/07/about-hall.jpg", fallback: "1775314054195-85f31de0c944", price: "KES 250,000", desc: "KICC's flagship hall — theatre, banquet and cocktail layouts." },
  { id: "amphitheatre", name: "Amphitheatre",      capacity: "800",   area: "1,800m²", priceDay: "KES 150,000", sqm: 1800, img: "1768590149213-8ab16aaf7511",                         fallback: "1768590149213-8ab16aaf7511", price: "KES 150,000", desc: "Open-air stage ideal for concerts, launches & outdoor events." },
  { id: "aberdares",    name: "Aberdares Hall",    capacity: "500",   area: "1,200m²", priceDay: "KES 120,000", sqm: 1200, img: "1775314054195-85f31de0c944",                         fallback: "1775314054195-85f31de0c944", price: "KES 120,000", desc: "Versatile hall with modular partitioning for breakout rooms." },
  { id: "lenana",       name: "Lenana Hills Room", capacity: "150",   area: "320m²",   priceDay: "KES 45,000",  sqm: 320,  img: "1782554981060-8549664cc0a8",                         fallback: "1782554981060-8549664cc0a8", price: "KES 45,000",  desc: "Executive boardroom with integrated AV and video conferencing." },
  { id: "shimba",       name: "Shimba Hills Suite",capacity: "80",    area: "180m²",   priceDay: "KES 25,000",  sqm: 180,  img: "https://kicc.co.ke/uploads/2026/07/about-sign.jpg",  fallback: "1592383809697-4986ac3151c6", price: "KES 25,000",  desc: "Intimate suite for VIP meetings, cocktails and small workshops." },
  { id: "courtyard",    name: "Courtyard",         capacity: "600",   area: "2,200m²", priceDay: "KES 95,000",  sqm: 2200, img: "1741991109886-90e70988f27b",                         fallback: "1741991109886-90e70988f27b", price: "KES 95,000",  desc: "Elegant outdoor terrace for garden parties and receptions." },
  { id: "lawn",         name: "KICC Lawn",         capacity: "1,200", area: "3,600m²", priceDay: "KES 80,000",  sqm: 3600, img: "1741991109902-98bf764fb35d",                         fallback: "1741991109902-98bf764fb35d", price: "KES 80,000",  desc: "Expansive manicured lawn for large outdoor galas and events." },
  { id: "comesa",       name: "COMESA Room",       capacity: "200",   area: "480m²",   priceDay: "KES 60,000",  sqm: 480,  img: "1552710307-537199cd41c0",                            fallback: "1552710307-537199cd41c0",    price: "KES 60,000",  desc: "Dedicated space for trade and inter-governmental meetings." },
];

export const EXHIBITIONS = [
  { id: "ex1", name: "Kenya International Trade Fair 2026",  dates: "2–12 Oct 2026",  venue: "KICC — Tsavo & Aberdares Halls", booths: 240, price: "KES 85,000", img: "1775314054195-85f31de0c944", status: "open" },
  { id: "ex2", name: "East Africa Digital Innovation Expo",  dates: "18–20 Nov 2026", venue: "KICC — Tsavo Hall",              booths: 120, price: "KES 45,000", img: "1760001551764-14eddf965019", status: "open" },
  { id: "ex3", name: "Nairobi International AgriExpo",       dates: "5–7 Dec 2026",   venue: "KICC — Courtyard & Lawn",        booths: 90,  price: "KES 35,000", img: "1760965254591-2819bdbda133", status: "soon" },
  { id: "ex4", name: "East Africa Health & Wellness Summit", dates: "22–24 Jan 2027",  venue: "KICC — Aberdares Hall",          booths: 60,  price: "KES 28,000", img: "1760385737059-c65b583ec23e", status: "soon" },
];

export const ENTITIES = [
  { id: "e1", name: "Hemingways Nairobi",              type: "Hotel",      rating: 4.8, price: "KES 28,000/night", img: "1607712617949-8c993d290809", tag: "5-Star Boutique" },
  { id: "e2", name: "Watamu Marine National Reserve",  type: "Attraction", rating: 4.9, price: "KES 1,200 entry",  img: "1652511928669-f3ce2797913b", tag: "UNESCO Heritage"  },
  { id: "e3", name: "Ol Pejeta Conservancy",           type: "Wildlife",   rating: 4.9, price: "KES 4,500/day",   img: "1709402606682-400133d92ab2", tag: "Big 5 Safari"     },
  { id: "e4", name: "Kazuri Beads Workshop",           type: "Experience", rating: 4.7, price: "KES 800/session", img: "1783024865247-d775f6b0b41b", tag: "Fair Trade Cert." },
  { id: "e5", name: "Diani Beach Resort & Spa",        type: "Hotel",      rating: 4.8, price: "KES 22,000/night", img: "1564490292125-2e3c78a0ef44", tag: "Beachfront Luxury" },
  { id: "e6", name: "Maasai Mara Tented Camp",         type: "Wildlife",   rating: 4.9, price: "KES 35,000/night", img: "1517503462743-f87ba95a8cae", tag: "Big 5 Safari"     },
];

export const SCREENS = [
  { id: "s1", name: "KICC Tower Rooftop LED",  location: "Nairobi CBD — visible 5km",    size: "48m²", price: "KES 120,000/week", img: "1760180139823-527522243bae" },
  { id: "s2", name: "Uhuru Highway Billboard", location: "Uhuru Hwy westbound, Nairobi", size: "32m²", price: "KES 85,000/week",  img: "1629150154933-a42577786d4f" },
  { id: "s3", name: "KICC Lobby Digital Wall", location: "KICC Ground Floor Lobby",      size: "12m²", price: "KES 45,000/week",  img: "1616418625172-c607e16733ca" },
  { id: "s4", name: "Mombasa Road Mega Screen",location: "Airport corridor, Nairobi",    size: "64m²", price: "KES 150,000/week", img: "1642110792431-a0e8f23cf34a" },
  { id: "s5", name: "Times Tower LED Display", location: "Upper Hill, Nairobi",          size: "28m²", price: "KES 70,000/week",  img: "1616418625298-baef98bc34f8" },
  { id: "s6", name: "Westlands Junction Screen",location: "Westlands Roundabout",        size: "20m²", price: "KES 55,000/week",  img: "1760180139823-527522243bae" },
];

export const chartRevenue = [
  { m: "Jan", v: 8.4 }, { m: "Feb", v: 10.2 }, { m: "Mar", v: 7.8 },
  { m: "Apr", v: 13.5 }, { m: "May", v: 11.9 }, { m: "Jun", v: 16.4 }, { m: "Jul", v: 14.7 },
];
export const PIE_DATA = [{ name: "Tourism", v: 34 }, { name: "Agri", v: 22 }, { name: "Tech", v: 18 }, { name: "Health", v: 14 }, { name: "Other", v: 12 }];
export const PIE_COLS = ["#901C1E", "#0B1E57", "#FFCD05", "#2D6A4F", "#E76F51"];

export const NAV_LINKS: [string, Page][] = [
  ["Counties", "county"], ["Directory", "directory"], ["Marketplace", "marketplace"],
  ["Exhibitions", "exhibition"], ["Venues", "venue"], ["Live Events", "live"], ["Screens", "screens"],
];

// ─── Utils ────────────────────────────────────────────────────────────────────
export const u = (id: string, w = 800, h = 500) =>
  `https://images.unsplash.com/photo-${id}?w=${w}&h=${h}&fit=crop&auto=format&q=80`;
export const fmt = (n: number) => n.toLocaleString("en-KE");

// ─── Counter ──────────────────────────────────────────────────────────────────
export function Counter({ to, suffix = "" }: { to: number; suffix?: string }) {
  const [val, setVal] = useState(0);
  useEffect(() => {
    let start = 0;
    const step = to / 60;
    const id = setInterval(() => { start += step; if (start >= to) { setVal(to); clearInterval(id); } else { setVal(Math.floor(start)); } }, 24);
    return () => clearInterval(id);
  }, [to]);
  return <span>{val.toLocaleString()}{suffix}</span>;
}

// ─── TiltCard ─────────────────────────────────────────────────────────────────
export function TiltCard({ children, className = "" }: { children: React.ReactNode; className?: string }) {
  const ref = useRef<HTMLDivElement>(null);
  const onMove = useCallback((e: React.MouseEvent<HTMLDivElement>) => {
    if (!ref.current) return;
    const { left, top, width, height } = ref.current.getBoundingClientRect();
    const x = ((e.clientX - left) / width  - 0.5) * 14;
    const y = ((e.clientY - top)  / height - 0.5) * -14;
    ref.current.style.transform = `perspective(900px) rotateY(${x}deg) rotateX(${y}deg) scale3d(1.02,1.02,1.02)`;
    ref.current.style.transition = "transform 0.1s ease-out";
  }, []);
  const onLeave = useCallback(() => {
    if (!ref.current) return;
    ref.current.style.transform = "perspective(900px) rotateY(0deg) rotateX(0deg) scale3d(1,1,1)";
    ref.current.style.transition = "transform 0.4s ease-out";
  }, []);
  return <div ref={ref} onMouseMove={onMove} onMouseLeave={onLeave} className={className}>{children}</div>;
}

// ─── PageWrap ─────────────────────────────────────────────────────────────────
export function PageWrap({ children }: { children: React.ReactNode }) {
  return (
    <motion.div initial={{ opacity: 0, y: 24 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -16 }}
      transition={{ duration: 0.38, ease: [0.22, 1, 0.36, 1] }}>
      {children}
    </motion.div>
  );
}

// ─── Reveal ───────────────────────────────────────────────────────────────────
export function Reveal({ children, delay = 0, className = "" }: { children: React.ReactNode; delay?: number; className?: string }) {
  return (
    <motion.div initial={{ opacity: 0, y: 36 }} whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: "-60px" }} transition={{ duration: 0.55, delay, ease: [0.22, 1, 0.36, 1] }} className={className}>
      {children}
    </motion.div>
  );
}

// ─── Pill ─────────────────────────────────────────────────────────────────────
export function Pill({ children, color = "gold", className = "" }: { children: React.ReactNode; color?: "gold" | "red" | "green" | "navy" | "gray"; className?: string }) {
  const cls = {
    gold:  "bg-[#FFCD05]/15 text-[#FFCD05] border-[#FFCD05]/30",
    red:   "bg-[#901C1E]/20 text-[#f87171] border-[#901C1E]/30",
    green: "bg-emerald-500/15 text-emerald-400 border-emerald-500/30",
    navy:  "bg-blue-500/15 text-blue-300 border-blue-500/30",
    gray:  "bg-white/10 text-white/60 border-white/10",
  }[color];
  return <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide border ${cls} ${className}`}>{children}</span>;
}

// ─── Btn ──────────────────────────────────────────────────────────────────────
export function Btn({ children, onClick, variant = "primary", size = "md", className = "", disabled = false, type = "button" }: {
  children: React.ReactNode; onClick?: () => void;
  variant?: "primary" | "ghost-light" | "outline-light" | "gold" | "dark";
  size?: "sm" | "md" | "lg"; className?: string; disabled?: boolean; type?: "button" | "submit";
}) {
  const base = "inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 cursor-pointer select-none shrink-0";
  const sizes = { sm: "px-4 text-xs h-9 rounded-xl", md: "px-6 text-sm h-12 rounded-xl", lg: "px-8 text-base h-14 rounded-xl" };
  const vars: Record<string, string> = {
    primary:        "bg-[#901C1E] text-white hover:bg-[#7b1618] active:scale-[0.97]",
    gold:           "bg-[#FFCD05] text-[#07090F] hover:bg-[#e6b904] active:scale-[0.97]",
    "ghost-light":  "text-white/70 hover:text-white hover:bg-white/10",
    "outline-light":"border border-white/25 text-white hover:bg-white/10 hover:border-white/50",
    dark:           "bg-white/10 text-white hover:bg-white/20 border border-white/15",
  };
  return (
    <motion.button whileTap={{ scale: 0.97 }} type={type} onClick={onClick} disabled={disabled}
      className={`${base} ${sizes[size]} ${vars[variant]} ${disabled ? "opacity-40 cursor-not-allowed" : ""} ${className}`}>
      {children}
    </motion.button>
  );
}

// ─── KICCLogo ─────────────────────────────────────────────────────────────────
export function KICCLogo({ onClick }: { onClick?: () => void }) {
  return (
    <button onClick={onClick} className="flex items-center gap-3 cursor-pointer group shrink-0">
      <img src={kiccLogo} alt="KICC" className="h-10 w-auto object-contain" style={{ filter: "brightness(0) invert(1)" }} />
      <div className="hidden sm:block leading-tight border-l border-white/20 pl-3">
        <div className="font-black text-white text-[11px] tracking-tight leading-tight group-hover:text-[#FFCD05] transition-colors uppercase">Global Exhibition</div>
        <div className="text-[9px] text-[#FFCD05] font-bold tracking-[0.18em] uppercase">Platform</div>
      </div>
    </button>
  );
}

// ─── Nav ──────────────────────────────────────────────────────────────────────
export function Nav({ navigate, cartCount = 0 }: { navigate: (p: Page) => void; cartCount: number }) {
  const [open, setOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  useEffect(() => {
    const fn = () => setScrolled(window.scrollY > 40);
    window.addEventListener("scroll", fn);
    return () => window.removeEventListener("scroll", fn);
  }, []);
  return (
    <motion.header className={`fixed top-0 left-0 right-0 z-50 transition-all duration-500 ${scrolled ? "bg-[#07090F]/95 backdrop-blur-xl border-b border-white/8 shadow-2xl shadow-black/40" : "bg-transparent"}`} style={{ height: 80 }}>
      <div className="max-w-7xl mx-auto px-5 h-full flex items-center justify-between gap-4">
        <KICCLogo onClick={() => navigate("home")} />
        <nav className="hidden md:flex items-center gap-1">
          {NAV_LINKS.map(([label, page]) => (
            <button key={page} onClick={() => navigate(page)} className="px-4 py-2 text-sm font-semibold text-white/70 hover:text-white hover:bg-white/8 rounded-lg transition-all cursor-pointer">{label}</button>
          ))}
        </nav>
        <div className="flex items-center gap-2">
          <button onClick={() => navigate("cart")} className="relative p-2 text-white/60 hover:text-white transition-colors cursor-pointer">
            <ShoppingCart size={20} />
            {cartCount > 0 && <span className="absolute -top-0.5 -right-0.5 w-4 h-4 bg-[#FFCD05] text-[#07090F] text-[10px] font-black rounded-full flex items-center justify-center">{cartCount}</span>}
          </button>
          <Btn onClick={() => navigate("login")} variant="outline-light" size="sm">Sign In</Btn>
          <button onClick={() => setOpen(!open)} className="md:hidden text-white/70 hover:text-white p-2 cursor-pointer"><Menu size={20} /></button>
        </div>
      </div>
      <AnimatePresence>
        {open && (
          <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -10 }}
            className="md:hidden absolute top-full left-0 right-0 bg-[#0D1220] border-b border-white/8 p-4 flex flex-col gap-1">
            {NAV_LINKS.map(([label, page]) => (
              <button key={page} onClick={() => { navigate(page); setOpen(false); }} className="text-left px-4 py-3 text-sm font-semibold text-white/70 hover:text-white hover:bg-white/8 rounded-lg cursor-pointer transition-colors">{label}</button>
            ))}
          </motion.div>
        )}
      </AnimatePresence>
    </motion.header>
  );
}

// ─── Footer ───────────────────────────────────────────────────────────────────
export function Footer({ navigate }: { navigate: (p: Page) => void }) {
  return (
    <footer className="bg-[#050709] border-t border-white/8 mt-20">
      <div className="max-w-7xl mx-auto px-5 py-14 grid grid-cols-1 md:grid-cols-4 gap-10">
        <div>
          <img src={kiccLogo} alt="KICC" className="h-12 w-auto object-contain mb-3" style={{ filter: "brightness(0) invert(1)" }} />
          <div className="text-[#FFCD05] text-[10px] font-bold tracking-[0.18em] uppercase mb-3">Global Exhibition Platform</div>
          <p className="text-white/40 text-sm leading-relaxed">Africa's Premier Meeting Venue. A national icon since 1973.</p>
          <div className="mt-5 flex flex-col gap-1 text-sm text-white/40">
            <span className="flex items-center gap-2"><Phone size={13} className="text-[#FFCD05]" /> (+254) 20 3261000</span>
            <span className="flex items-center gap-2"><MapPin size={13} className="text-[#FFCD05]" /> City Square, Nairobi CBD</span>
          </div>
        </div>
        {[
          { title: "Platform",    links: [["Counties","county"],["Directory","directory"],["Marketplace","marketplace"],["Exhibitions","exhibition"],["Live Events","live"],["Screens","screens"]] },
          { title: "Dashboards",  links: [["Seller Portal","dash-seller"],["County Admin","dash-county"],["Exhibitor Pro","dash-exhibitor"],["Platform Admin","dash-admin"]] },
          { title: "Information", links: [["About KICC","home"],["Pricing","home"],["Sustainability","home"],["Contact Us","home"]] },
        ].map(({ title, links }) => (
          <div key={title}>
            <h4 className="font-bold text-white/60 text-xs uppercase tracking-[0.15em] mb-4">{title}</h4>
            <ul className="space-y-2.5">
              {links.map(([l, p]) => (
                <li key={l}><button onClick={() => navigate(p as Page)} className="text-white/40 hover:text-[#FFCD05] text-sm transition-colors cursor-pointer">{l}</button></li>
              ))}
            </ul>
          </div>
        ))}
      </div>
      <div className="border-t border-white/5 py-5">
        <div className="max-w-7xl mx-auto px-5 flex flex-col md:flex-row justify-between items-center gap-2 text-white/25 text-xs">
          <span>© 2026 Kenyatta International Convention Centre. All rights reserved.</span>
          <span className="flex items-center gap-1.5"><Shield size={11} /> KRA · CBK · KEBS Compliant</span>
        </div>
      </div>
    </footer>
  );
}

// ─── SectionHead ──────────────────────────────────────────────────────────────
export function SectionHead({ eyebrow, title, sub }: { eyebrow?: string; title: React.ReactNode; sub?: string }) {
  return (
    <div className="mb-10">
      {eyebrow && (
        <div className="flex items-center gap-3 mb-3">
          <div className="h-px w-8 bg-[#FFCD05]" />
          <span className="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">{eyebrow}</span>
        </div>
      )}
      <h2 className="text-3xl md:text-4xl font-black text-white leading-[1.1]">{title}</h2>
      {sub && <p className="text-white/40 mt-3 text-base max-w-xl leading-relaxed">{sub}</p>}
    </div>
  );
}

// ─── KpiCard ──────────────────────────────────────────────────────────────────
export function KpiCard({ label, value, change, icon: Icon, accent = "#901C1E" }: { label: string; value: string; change?: string; icon: React.ElementType; accent?: string }) {
  return (
    <motion.div whileHover={{ y: -3 }} className="bg-[#0D1220] border border-white/8 rounded-2xl p-5 hover:border-white/15 transition-all">
      <div className="flex items-start justify-between mb-4">
        <div className="p-2.5 rounded-xl" style={{ background: accent + "22" }}><Icon size={17} style={{ color: accent }} /></div>
        {change && <span className="text-[10px] font-bold text-emerald-400 bg-emerald-400/10 border border-emerald-400/20 px-2 py-0.5 rounded-full">{change}</span>}
      </div>
      <div className="text-2xl font-black text-white">{value}</div>
      <div className="text-xs text-white/35 mt-1 font-medium">{label}</div>
    </motion.div>
  );
}

// ─── DashShell ────────────────────────────────────────────────────────────────
export function DashShell({ title, role, navigate, navItems, children, accentColor = "#901C1E", initials = "JK" }: {
  title: string; role: string; navigate: (p: Page) => void;
  navItems: { icon: React.ElementType; label: string; active?: boolean; onClick?: () => void }[];
  children: React.ReactNode; accentColor?: string; initials?: string;
}) {
  const [collapsed, setCollapsed] = useState(false);
  return (
    <div className="flex h-screen overflow-hidden bg-[#07090F]">
      <motion.div animate={{ width: collapsed ? 64 : 230 }} transition={{ duration: 0.25 }}
        className="bg-[#050709] border-r border-white/8 flex flex-col overflow-hidden shrink-0">
        <div className="flex items-center justify-between px-4 border-b border-white/8 h-16">
          {!collapsed && <div className="font-black text-white text-xs tracking-tight leading-tight"><div>KICC</div><div className="text-[#FFCD05] text-[9px] tracking-[0.15em] uppercase">Global Exhibition</div></div>}
          <button onClick={() => setCollapsed(c => !c)} className="text-white/40 hover:text-white cursor-pointer p-1 ml-auto"><Menu size={16} /></button>
        </div>
        <div className="flex-1 py-3 overflow-y-auto">
          {navItems.map(({ icon: Icon, label, active, onClick }) => (
            <button key={label} onClick={onClick}
              className={`w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-all cursor-pointer ${active ? "text-white" : "text-white/35 hover:bg-white/5 hover:text-white"}`}
              style={active ? { background: accentColor + "22", borderRight: `2px solid ${accentColor}` } : {}}>
              <Icon size={16} className="shrink-0" />
              {!collapsed && <span className="truncate text-xs">{label}</span>}
            </button>
          ))}
        </div>
        <button onClick={() => navigate("home")} className="flex items-center gap-3 px-4 py-4 border-t border-white/8 text-white/30 hover:text-white text-xs cursor-pointer transition-colors">
          <LogOut size={15} className="shrink-0" />
          {!collapsed && <span>Exit Dashboard</span>}
        </button>
      </motion.div>
      <div className="flex-1 flex flex-col overflow-hidden">
        <div className="bg-[#050709] border-b border-white/8 px-6 h-16 flex items-center justify-between shrink-0">
          <div>
            <div className="font-black text-white text-sm">{title}</div>
            <div className="text-[10px] font-bold uppercase tracking-widest" style={{ color: accentColor }}>{role}</div>
          </div>
          <div className="flex items-center gap-3">
            <button className="relative p-2 text-white/40 hover:text-white cursor-pointer transition-colors">
              <Bell size={16} /><div className="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-[#901C1E] rounded-full" />
            </button>
            <div className="w-8 h-8 rounded-full flex items-center justify-center text-white font-black text-xs" style={{ background: accentColor }}>{initials}</div>
          </div>
        </div>
        <div className="flex-1 overflow-y-auto p-6">{children}</div>
      </div>
    </div>
  );
}

// ─── EditField ────────────────────────────────────────────────────────────────
export function EditField({ label, value, onChange, defaultValue, type = "text", multiline = false, hint }: {
  label: string; value?: string; onChange?: (v: string) => void; defaultValue?: string; type?: string; multiline?: boolean; hint?: string;
}) {
  const controlled = value !== undefined && onChange !== undefined;
  return (
    <div>
      <label className="block text-[10px] font-bold text-white/35 uppercase tracking-wider mb-1.5">{label}</label>
      {multiline
        ? <textarea defaultValue={controlled ? undefined : defaultValue} value={controlled ? value : undefined} onChange={controlled ? e => onChange(e.target.value) : undefined} rows={3} className="w-full bg-[#141B2E] border border-white/8 focus:border-[#FFCD05]/50 rounded-xl px-4 py-3 text-sm text-white outline-none resize-none transition-colors" />
        : <input type={type} defaultValue={controlled ? undefined : defaultValue} value={controlled ? value : undefined} onChange={controlled ? e => onChange(e.target.value) : undefined} className="w-full bg-[#141B2E] border border-white/8 focus:border-[#FFCD05]/50 rounded-xl px-4 py-2.5 text-sm text-white outline-none transition-colors" />
      }
      {hint && <p className="mt-1 text-[10px] text-white/30">{hint}</p>}
    </div>
  );
}

// ─── ImageEditor ──────────────────────────────────────────────────────────────
export function ImageEditor({ label, value, onChange, defaultValue = "1547471080-7cc2caa01a7e", hint }: {
  label: string; value?: string; onChange?: (v: string) => void; defaultValue?: string; hint?: string;
}) {
  return (
    <div>
      <label className="block text-[10px] font-bold text-white/35 uppercase tracking-wider mb-1.5">{label}</label>
      <div className="relative rounded-xl overflow-hidden border border-white/8 hover:border-[#FFCD05]/40 transition-all">
        <img src={u(value ?? defaultValue, 600, 200)} alt="" className="w-full h-28 object-cover" onError={e => { (e.target as HTMLImageElement).src = u("1547471080-7cc2caa01a7e", 600, 200); }} />
        <div className="absolute inset-0 bg-black/60 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
          <div className="text-center"><Upload size={20} className="text-white mx-auto mb-1" /><div className="text-white text-xs font-bold">Change Image</div></div>
        </div>
      </div>
      <input defaultValue={value !== undefined ? undefined : defaultValue} value={value} onChange={onChange ? e => onChange(e.target.value) : undefined} placeholder="Unsplash photo ID or image URL"
        className="w-full mt-2 bg-[#141B2E] border border-white/8 focus:border-[#FFCD05]/50 rounded-xl px-4 py-2 text-xs text-white/60 outline-none transition-colors font-mono" />
    </div>
  );
}
