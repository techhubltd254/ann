// ─────────────────────────────────────────────────────────────
// Murang'a County — KICC Platform Admin Mock Data
// ─────────────────────────────────────────────────────────────

export type Status = 'active' | 'featured' | 'review' | 'pending';

// ── Revenue & Analytics ──────────────────────────────────────

export const revenueData = [
  { month: 'Jan', tourism: 412000, marketplace: 168000, advertising: 84000 },
  { month: 'Feb', tourism: 438000, marketplace: 182000, advertising: 91000 },
  { month: 'Mar', tourism: 486000, marketplace: 196000, advertising: 102000 },
  { month: 'Apr', tourism: 455000, marketplace: 214000, advertising: 98000 },
  { month: 'May', tourism: 524000, marketplace: 238000, advertising: 115000 },
  { month: 'Jun', tourism: 598000, marketplace: 262000, advertising: 128000 },
  { month: 'Jul', tourism: 642000, marketplace: 289000, advertising: 141000 },
  { month: 'Aug', tourism: 715000, marketplace: 318000, advertising: 156000 },
];

export const sectorDistribution = [
  { name: 'Tourism', value: 32, color: '#6366F1' },
  { name: 'Agriculture', value: 27, color: '#10B981' },
  { name: 'Hospitality', value: 18, color: '#06B6D4' },
  { name: 'Commerce', value: 13, color: '#F59E0B' },
  { name: 'Culture & Heritage', value: 10, color: '#F43F5E' },
];

export const trafficData = [
  { day: 'Mon', visitors: 2840, pageviews: 8120, bookings: 46 },
  { day: 'Tue', visitors: 3120, pageviews: 8940, bookings: 52 },
  { day: 'Wed', visitors: 2980, pageviews: 8610, bookings: 49 },
  { day: 'Thu', visitors: 3460, pageviews: 10280, bookings: 61 },
  { day: 'Fri', visitors: 4230, pageviews: 12940, bookings: 78 },
  { day: 'Sat', visitors: 5680, pageviews: 17320, bookings: 104 },
  { day: 'Sun', visitors: 5140, pageviews: 15180, bookings: 92 },
];

export const channelData = [
  { channel: 'Organic Search', sessions: 12480, conversion: 4.2, color: '#6366F1' },
  { channel: 'Direct', sessions: 8920, conversion: 5.8, color: '#10B981' },
  { channel: 'Social Media', sessions: 6340, conversion: 2.9, color: '#06B6D4' },
  { channel: 'Referral', sessions: 3180, conversion: 3.6, color: '#F59E0B' },
  { channel: 'Email', sessions: 1940, conversion: 6.4, color: '#F43F5E' },
];

// ── Tourism & Hospitality ────────────────────────────────────

export const mockAttractions = [
  { id: 'ATT-001', name: 'Twin Falls', location: 'Kangema', category: 'Waterfall', status: 'featured' as Status, visitors: '12,400/mo', rating: 4.8, price: 500, emoji: '💧', description: 'Iconic twin cascades surrounded by indigenous forest canopy.' },
  { id: 'ATT-002', name: 'Aberdare Eco Routes', location: 'Kangari', category: 'Eco Trail', status: 'active' as Status, visitors: '8,200/mo', rating: 4.9, price: 1200, emoji: '🏔️', description: 'Guided highland trekking routes along the Aberdare ranges.' },
  { id: 'ATT-003', name: 'Sagana Waterfalls', location: 'Sagana', category: 'Waterfall', status: 'active' as Status, visitors: '9,800/mo', rating: 4.7, price: 650, emoji: '🌊', description: 'White-water rapids and falls on the Sagana River.' },
  { id: 'ATT-004', name: 'Gathwori Dam', location: 'Kiharu', category: 'Dam & Lake', status: 'review' as Status, visitors: '3,100/mo', rating: 4.3, price: 300, emoji: '🚣', description: 'Serene dam offering boating, fishing and picnic sites.' },
  { id: 'ATT-005', name: 'Michuki Eco-Trail', location: 'Kigumo', category: 'Nature Walk', status: 'pending' as Status, visitors: '2,400/mo', rating: 4.6, price: 400, emoji: '🌿', description: 'Legacy conservation trail named after the late John Michuki.' },
];

export const mockHotels = [
  { id: 'HTL-001', name: 'Elipa Hotel', location: "Murang'a Town", stars: 4, rooms: 48, occupancy: 86, status: 'featured' as Status, price: 8500, emoji: '🏨' },
  { id: 'HTL-002', name: 'Sky Garden Suites', location: 'Kenol', stars: 3, rooms: 32, occupancy: 78, status: 'active' as Status, price: 6200, emoji: '🌇' },
  { id: 'HTL-003', name: 'Highland Palace', location: 'Kangema', stars: 3, rooms: 26, occupancy: 71, status: 'active' as Status, price: 5400, emoji: '🏰' },
  { id: 'HTL-004', name: 'Thika Resort', location: 'Thika Corridor', stars: 5, rooms: 84, occupancy: 92, status: 'review' as Status, price: 14800, emoji: '🌴' },
];

export const mockProducts = [
  { id: 'PRD-001', name: "Murang'a Tea", category: 'Beverages', stock: 240, sold: 1240, status: 'featured' as Status, price: 450, emoji: '🍵', vendor: 'Gatanga Tea Cooperative' },
  { id: 'PRD-002', name: 'Organic Honey', category: 'Farm Produce', stock: 180, sold: 860, status: 'active' as Status, price: 800, emoji: '🍯', vendor: 'Kangema Apiary Group' },
  { id: 'PRD-003', name: 'Handwoven Baskets', category: 'Crafts', stock: 95, sold: 420, status: 'active' as Status, price: 1500, emoji: '🧺', vendor: "Kiharu Women's Guild" },
  { id: 'PRD-004', name: 'Coffee Beans', category: 'Beverages', stock: 320, sold: 1580, status: 'featured' as Status, price: 950, emoji: '☕', vendor: 'Mathioya Coffee Society' },
  { id: 'PRD-005', name: 'Macadamia Nuts', category: 'Farm Produce', stock: 410, sold: 980, status: 'active' as Status, price: 1100, emoji: '🌰', vendor: 'Maragua Growers Co-op' },
  { id: 'PRD-006', name: 'Traditional Crafts', category: 'Crafts', stock: 64, sold: 310, status: 'review' as Status, price: 2200, emoji: '🪘', vendor: 'Kigumo Artisans Collective' },
];

export const mockListings = [
  { id: 'LST-001', name: "Murang'a Tea — 500g Premium", vendor: 'Gatanga Tea Cooperative', category: 'Beverages', price: 450, orders: 342, status: 'featured' as Status, emoji: '🍵' },
  { id: 'LST-002', name: 'Twin Falls Guided Hike', vendor: 'Aberdare Adventures', category: 'Experience', price: 3500, orders: 128, status: 'active' as Status, emoji: '🥾' },
  { id: 'LST-003', name: 'Organic Honey — 1L Jar', vendor: 'Kangema Apiary Group', category: 'Farm Produce', price: 800, orders: 214, status: 'active' as Status, emoji: '🍯' },
  { id: 'LST-004', name: 'Sagana White-Water Rafting', vendor: 'Rapids Camp Ltd', category: 'Experience', price: 7500, orders: 86, status: 'review' as Status, emoji: '🛶' },
  { id: 'LST-005', name: 'Handwoven Kiondo Basket', vendor: "Kiharu Women's Guild", category: 'Crafts', price: 1500, orders: 97, status: 'active' as Status, emoji: '🧺' },
  { id: 'LST-006', name: 'Macadamia Gift Box — 2kg', vendor: 'Maragua Growers Co-op', category: 'Farm Produce', price: 2400, orders: 153, status: 'pending' as Status, emoji: '🎁' },
];

// ── Commerce ─────────────────────────────────────────────────

export const mockOrders = [
  { id: 'ORD-7841', name: 'Twin Falls Group Tour', customer: 'Wanjiku Mwangi', type: 'Booking', items: 4, date: 'Aug 22, 2026', status: 'active' as Status, price: 14000, emoji: '🎫' },
  { id: 'ORD-7840', name: "Murang'a Tea — 500g ×6", customer: 'Kamau Njoroge', type: 'Product', items: 6, date: 'Aug 22, 2026', status: 'active' as Status, price: 2700, emoji: '📦' },
  { id: 'ORD-7839', name: 'Elipa Hotel — 2 Nights', customer: 'Achieng Odhiambo', type: 'Booking', items: 1, date: 'Aug 21, 2026', status: 'featured' as Status, price: 17000, emoji: '🛎️' },
  { id: 'ORD-7838', name: 'Organic Honey — 1L ×3', customer: 'Muthoni Wairimu', type: 'Product', items: 3, date: 'Aug 21, 2026', status: 'active' as Status, price: 2400, emoji: '📦' },
  { id: 'ORD-7837', name: 'Aberdare Eco Route Trek', customer: 'Otieno Kiprop', type: 'Booking', items: 2, date: 'Aug 20, 2026', status: 'review' as Status, price: 2400, emoji: '🎫' },
  { id: 'ORD-7836', name: 'Handwoven Baskets ×2', customer: 'Nyambura Maina', type: 'Product', items: 2, date: 'Aug 20, 2026', status: 'active' as Status, price: 3000, emoji: '📦' },
  { id: 'ORD-7835', name: 'Thika Resort — Weekend pkg', customer: 'Baraka Mwangi', type: 'Booking', items: 1, date: 'Aug 19, 2026', status: 'pending' as Status, price: 29600, emoji: '🛎️' },
  { id: 'ORD-7834', name: 'Coffee Beans — 1kg ×4', customer: 'Wambui Gichuki', type: 'Product', items: 4, date: 'Aug 19, 2026', status: 'active' as Status, price: 3800, emoji: '📦' },
  { id: 'ORD-7833', name: 'Sagana Rafting Experience', customer: 'Njeri Karanja', type: 'Booking', items: 3, date: 'Aug 18, 2026', status: 'review' as Status, price: 22500, emoji: '🎫' },
  { id: 'ORD-7832', name: 'Macadamia Nuts — 1kg ×2', customer: 'Chebet Rono', type: 'Product', items: 2, date: 'Aug 18, 2026', status: 'active' as Status, price: 2200, emoji: '📦' },
];

// ── Immersive Media ──────────────────────────────────────────

export const mock4DScenes = [
  { id: '4D-001', title: 'Twin Falls — Volumetric Walkthrough', subtitle: 'Full Gaussian splat capture of the twin cascades', duration: '2:34', resolution: '8K', engine: 'WebGPU Splat v2', frames: 9216, status: 'active' as Status, emoji: '🌊', size: '4.2 GB' },
  { id: '4D-002', title: 'Aberdare Canopy — Dawn Pass', subtitle: 'Scroll-triggered cinematic over the forest canopy', duration: '1:48', resolution: '6K', engine: 'WebGPU Splat v2', frames: 6480, status: 'active' as Status, emoji: '🌄', size: '3.1 GB' },
  { id: '4D-003', title: 'Sagana Rapids — Immersive Flow', subtitle: 'Volumetric water-surface reconstruction', duration: '3:02', resolution: '8K', engine: 'WebGPU Splat v2', frames: 10944, status: 'review' as Status, emoji: '🛶', size: '5.6 GB' },
  { id: '4D-004', title: 'Elipa Hotel — Lobby Splat', subtitle: 'Interior capture for virtual hospitality tours', duration: '1:12', resolution: '4K', engine: 'WebGPU Splat v2', frames: 4320, status: 'pending' as Status, emoji: '🏨', size: '1.8 GB' },
];

export const mockHeroVideos = [
  { id: 'HRV-001', name: "Discover Murang'a — Official Film", duration: '4:12', resolution: '4K HDR', placement: 'Portal Hero', status: 'featured' as Status, emoji: '🎬', views: '48.2K' },
  { id: 'HRV-002', name: 'Twin Falls Cinematic Loop', duration: '0:45', resolution: '4K', placement: 'Attractions Hero', status: 'active' as Status, emoji: '💧', views: '31.7K' },
  { id: 'HRV-003', name: 'Highland Tea Journey', duration: '2:58', resolution: '4K HDR', placement: 'Products Hero', status: 'active' as Status, emoji: '🍃', views: '22.4K' },
  { id: 'HRV-004', name: 'Aberdare Sunrise Timelapse', duration: '1:20', resolution: '6K', placement: 'Hotels Hero', status: 'review' as Status, emoji: '🌅', views: '12.9K' },
  { id: 'HRV-005', name: 'County Investment Reel', duration: '3:40', resolution: '4K', placement: 'Sectors Hero', status: 'pending' as Status, emoji: '📈', views: '8.1K' },
];

export const mockGalleryImages = [
  { id: 'IMG-001', name: 'twin-falls-aerial-01.cr3', category: 'Attractions', size: '24.6 MB', dimensions: '8192×5464', status: 'active' as Status, emoji: '💧' },
  { id: 'IMG-002', name: 'aberdare-canopy-dawn.jpg', category: 'Attractions', size: '18.2 MB', dimensions: '7952×5304', status: 'active' as Status, emoji: '🏔️' },
  { id: 'IMG-003', name: 'elipa-lobby-interior.cr3', category: 'Hotels', size: '22.1 MB', dimensions: '6720×4480', status: 'featured' as Status, emoji: '🏨' },
  { id: 'IMG-004', name: 'tea-harvest-gatanga.jpg', category: 'Products', size: '15.8 MB', dimensions: '6000×4000', status: 'active' as Status, emoji: '🍵' },
  { id: 'IMG-005', name: 'sagana-rapids-kayak.cr3', category: 'Attractions', size: '26.4 MB', dimensions: '8192×5464', status: 'review' as Status, emoji: '🛶' },
  { id: 'IMG-006', name: 'macadamia-orchard.jpg', category: 'Products', size: '14.2 MB', dimensions: '5472×3648', status: 'active' as Status, emoji: '🌰' },
  { id: 'IMG-007', name: 'sky-garden-suite-room.cr3', category: 'Hotels', size: '20.9 MB', dimensions: '6720×4480', status: 'active' as Status, emoji: '🌇' },
  { id: 'IMG-008', name: 'kiondo-weaving-craft.jpg', category: 'Culture', size: '12.6 MB', dimensions: '5472×3648', status: 'pending' as Status, emoji: '🧺' },
  { id: 'IMG-009', name: 'gathwori-dam-sunset.cr3', category: 'Attractions', size: '23.8 MB', dimensions: '8192×5464', status: 'active' as Status, emoji: '🌅' },
  { id: 'IMG-010', name: 'coffee-cherries-mathioya.jpg', category: 'Products', size: '16.4 MB', dimensions: '6000×4000', status: 'active' as Status, emoji: '☕' },
  { id: 'IMG-011', name: 'highland-palace-exterior.cr3', category: 'Hotels', size: '21.7 MB', dimensions: '6720×4480', status: 'review' as Status, emoji: '🏰' },
  { id: 'IMG-012', name: 'michuki-trail-forest.jpg', category: 'Attractions', size: '17.3 MB', dimensions: '7952×5304', status: 'active' as Status, emoji: '🌿' },
];

export const mockArticles = [
  { id: 'ART-001', name: "Twin Falls Named Among Kenya's Top Hidden Gems", author: 'W. Njoki', category: 'Tourism News', status: 'featured' as Status, date: 'Aug 20, 2026', views: '14.2K', emoji: '📰' },
  { id: 'ART-002', name: "Murang'a Tea Exports Surge 34% in Q2", author: 'K. Maina', category: 'Agribusiness', status: 'active' as Status, date: 'Aug 18, 2026', views: '9.8K', emoji: '📰' },
  { id: 'ART-003', name: 'New Eco-Trail Opens Along Aberdare Ridge', author: 'W. Njoki', category: 'Tourism News', status: 'active' as Status, date: 'Aug 15, 2026', views: '7.1K', emoji: '📰' },
  { id: 'ART-004', name: 'County Partners with Elipa Hotel for Expo', author: 'J. Mwangi', category: 'Partnerships', status: 'review' as Status, date: 'Aug 12, 2026', views: '4.6K', emoji: '📰' },
  { id: 'ART-005', name: "Macadamia Farmers' Co-op Goes Digital", author: 'K. Maina', category: 'Agribusiness', status: 'pending' as Status, date: 'Aug 10, 2026', views: '2.9K', emoji: '📰' },
];

// ── Commercial Ops ───────────────────────────────────────────

export const mockPricingRules = [
  { id: 'PRC-001', name: 'Peak Season Uplift', target: 'Attraction Tickets', modifier: '+25%', window: 'Dec – Jan', status: 'active' as Status, impact: '+KES 184K/mo' },
  { id: 'PRC-002', name: 'Weekend Hotel Rates', target: 'Hotel Bookings', modifier: '+15%', window: 'Fri – Sun', status: 'active' as Status, impact: '+KES 96K/mo' },
  { id: 'PRC-003', name: 'Group Discount (5+)', target: 'Tour Bookings', modifier: '-10%', window: 'All year', status: 'active' as Status, impact: '+412 bookings' },
  { id: 'PRC-004', name: 'Harvest Season Promo', target: 'Farm Produce', modifier: '-15%', window: 'Sep – Nov', status: 'review' as Status, impact: 'Projected +22% volume' },
  { id: 'PRC-005', name: 'Last-Minute Deal', target: 'Hotel Bookings', modifier: '-20%', window: '< 48h check-in', status: 'pending' as Status, impact: 'Draft' },
];

export const mockCampaigns = [
  { id: 'CMP-001', name: "Visit Murang'a — August Drive", channel: 'Social + Display', budget: 250000, spent: 168400, impressions: '1.24M', clicks: '38.2K', ctr: 3.1, status: 'active' as Status },
  { id: 'CMP-002', name: 'Twin Falls Weekend Push', channel: 'Search Ads', budget: 120000, spent: 98200, impressions: '486K', clicks: '21.4K', ctr: 4.4, status: 'active' as Status },
  { id: 'CMP-003', name: 'Tea & Coffee Harvest Fest', channel: 'Social', budget: 180000, spent: 42100, impressions: '392K', clicks: '9.8K', ctr: 2.5, status: 'review' as Status },
  { id: 'CMP-004', name: 'Sagana Adventure Season', channel: 'Video Ads', budget: 320000, spent: 0, impressions: '—', clicks: '—', ctr: 0, status: 'pending' as Status },
];

export const mockPackages = [
  { id: 'PKG-001', name: 'Tourism Starter', price: 4500, period: '/mo', subscribers: 128, status: 'active' as Status, emoji: '🧭', features: ['Listing on county portal', 'Basic analytics', 'Standard support', '1 promotional slot'] },
  { id: 'PKG-002', name: 'Hospitality Pro', price: 12000, period: '/mo', subscribers: 46, status: 'featured' as Status, emoji: '🏨', features: ['Everything in Starter', 'Booking engine integration', 'Hero video placement', 'Priority support', 'Dynamic pricing tools'] },
  { id: 'PKG-003', name: 'Agri Market Plus', price: 8500, period: '/mo', subscribers: 84, status: 'active' as Status, emoji: '🌾', features: ['Everything in Starter', 'Marketplace storefront', 'Bulk order tooling', 'Logistics partner access'] },
  { id: 'PKG-004', name: 'County Enterprise', price: 35000, period: '/mo', subscribers: 12, status: 'active' as Status, emoji: '👑', features: ['Everything in Pro', '4D splat capture pipeline', 'Dedicated account manager', 'API access & exports', 'Co-branded campaigns'] },
];

// ── System ───────────────────────────────────────────────────

export const mockSectors = [
  { id: 'SEC-001', name: 'Tourism Board', institutions: 24, lead: 'M. Wanjiru', status: 'active' as Status, emoji: '🧭', budget: 'KES 8.4M' },
  { id: 'SEC-002', name: 'Agriculture & Co-operatives', institutions: 58, lead: 'P. Kamau', status: 'active' as Status, emoji: '🌾', budget: 'KES 12.1M' },
  { id: 'SEC-003', name: 'Hospitality Association', institutions: 17, lead: 'R. Njeri', status: 'active' as Status, emoji: '🏨', budget: 'KES 5.2M' },
  { id: 'SEC-004', name: 'Trade & Commerce', institutions: 41, lead: 'D. Mwangi', status: 'active' as Status, emoji: '🏪', budget: 'KES 6.8M' },
  { id: 'SEC-005', name: 'Culture & Heritage', institutions: 12, lead: 'S. Wambui', status: 'review' as Status, emoji: '🎭', budget: 'KES 3.1M' },
  { id: 'SEC-006', name: 'ICT & Innovation Hub', institutions: 9, lead: 'J. Kiprop', status: 'pending' as Status, emoji: '💡', budget: 'KES 4.5M' },
];

export const mockReports = [
  { id: 'RPT-001', name: 'Monthly Revenue Summary', description: 'Consolidated revenue across tourism, marketplace and ads', format: 'PDF / XLSX', schedule: 'Monthly', lastRun: 'Aug 1, 2026' },
  { id: 'RPT-002', name: 'Visitor & Footfall Analytics', description: 'Attraction visits, hotel occupancy, portal traffic', format: 'PDF', schedule: 'Weekly', lastRun: 'Aug 18, 2026' },
  { id: 'RPT-003', name: 'Marketplace Performance', description: 'Product sales, vendor payouts, order fulfilment rates', format: 'XLSX / CSV', schedule: 'Weekly', lastRun: 'Aug 18, 2026' },
  { id: 'RPT-004', name: 'Campaign ROI Report', description: 'Ad spend vs attributed bookings and sales', format: 'PDF', schedule: 'On demand', lastRun: 'Aug 12, 2026' },
  { id: 'RPT-005', name: 'Sector Institutional Audit', description: 'Institution registrations, budgets and compliance', format: 'PDF / CSV', schedule: 'Quarterly', lastRun: 'Jul 1, 2026' },
];

export const mockExports = [
  { id: 'EXP-1042', name: 'revenue-summary-jul-2026.pdf', size: '2.4 MB', by: 'M. County Admin', date: 'Aug 1, 2026', status: 'active' as Status, emoji: '📄' },
  { id: 'EXP-1041', name: 'footfall-w33-2026.pdf', size: '1.8 MB', by: 'W. Njoki', date: 'Aug 18, 2026', status: 'active' as Status, emoji: '📄' },
  { id: 'EXP-1040', name: 'marketplace-w33.xlsx', size: '864 KB', by: 'K. Maina', date: 'Aug 18, 2026', status: 'active' as Status, emoji: '📊' },
  { id: 'EXP-1039', name: 'campaign-roi-aug.pdf', size: '1.1 MB', by: 'M. County Admin', date: 'Aug 12, 2026', status: 'active' as Status, emoji: '📄' },
];

export const mockAuditLogs = [
  { id: 'AUD-9921', actor: 'admin@muranga.go.ke', action: 'UPDATE', target: 'Attraction / Twin Falls', detail: 'Changed entry fee 400 → 500 KES', time: '12m ago', ip: '41.90.64.12', level: 'info' },
  { id: 'AUD-9920', actor: 'editor@kicc.org', action: 'PUBLISH', target: 'Article / Tea Exports Surge', detail: 'Published to portal homepage', time: '48m ago', ip: '102.133.20.8', level: 'info' },
  { id: 'AUD-9919', actor: 'admin@muranga.go.ke', action: 'CREATE', target: '4D Scene / Elipa Lobby Splat', detail: 'Queued volumetric capture job', time: '2h ago', ip: '41.90.64.12', level: 'info' },
  { id: 'AUD-9918', actor: 'finance@kicc.org', action: 'EXPORT', target: 'Report / Marketplace W33', detail: 'XLSX export, 214 rows', time: '3h ago', ip: '196.201.14.55', level: 'info' },
  { id: 'AUD-9917', actor: 'ops@muranga.go.ke', action: 'DELETE', target: 'Listing / Duplicate Basket Ad', detail: 'Removed duplicate marketplace listing', time: '5h ago', ip: '41.90.71.201', level: 'warning' },
  { id: 'AUD-9916', actor: 'admin@muranga.go.ke', action: 'ROLE_CHANGE', target: 'User / editor@kicc.org', detail: 'Granted Content Publisher role', time: '1d ago', ip: '41.90.64.12', level: 'critical' },
  { id: 'AUD-9915', actor: 'system', action: 'BACKUP', target: 'Media Vault', detail: 'Nightly snapshot · 128 assets · 96 GB', time: '1d ago', ip: '—', level: 'info' },
  { id: 'AUD-9914', actor: 'ops@muranga.go.ke', action: 'LOGIN_FAIL', target: 'Account / ops@muranga.go.ke', detail: '3 failed attempts · locked 15 min', time: '2d ago', ip: '197.232.11.90', level: 'critical' },
];

export const mockRoles = [
  { id: 'ROL-001', name: 'County Administrator', members: 2, permissions: { content: true, commerce: true, media: true, settings: true, billing: true } },
  { id: 'ROL-002', name: 'Content Publisher', members: 5, permissions: { content: true, commerce: false, media: true, settings: false, billing: false } },
  { id: 'ROL-003', name: 'Commercial Manager', members: 3, permissions: { content: false, commerce: true, media: false, settings: false, billing: true } },
  { id: 'ROL-004', name: 'Media Technician', members: 4, permissions: { content: false, commerce: false, media: true, settings: false, billing: false } },
  { id: 'ROL-005', name: 'Read-Only Auditor', members: 6, permissions: { content: false, commerce: false, media: false, settings: false, billing: false } },
];
