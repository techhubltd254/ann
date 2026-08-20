#!/usr/bin/env python3
"""Generate two PDFs — equipment only + subscriptions only (grouped)."""

from fpdf import FPDF

USD_RATE = 129.5
TX_COST = 1.05

# (category, name, qty_key, specs, unit_price_ksh)
ITEMS = [
    # ── 1. Cameras ──
    ("1. Cameras", "Canon EOS C50 Cinema Camera (RF Mount)", "qty_1",
     "Cinema EOS, 6K Super35 CMOS\n"
     "4K120p 10-bit 4:2:2, Dual Pixel AF II\n"
     "RF Mount, Mini XLR, compact body",
     468600),
    ("1. Cameras", "Canon EOS R5 Mark II (Body Only)", "qty_1",
     "45MP Stacked BSI CMOS\n"
     "8K60p Raw / 4K120p 10-bit\n"
     "30fps e-shutter, DIGIC X + Accelerator",
     385440),
    # ── 2. Lenses ──
    ("2. Lenses", "Canon RF 15-35mm f/2.8L IS USM", "qty_1",
     "Wide-angle zoom, constant f/2.8\n"
     "5-stop Optical IS, Nano USM\n"
     "Weather-sealed, 82mm filter",
     216480),
    ("2. Lenses", "Canon RF 24-105mm f/2.8L IS USM Z", "qty_1",
     "Constant f/2.8 standard zoom\n"
     "11-blade iris, parfocal, 5.5-stop IS\n"
     "Internal zoom, extender compatible",
     402600),
    ("2. Lenses", "Canon RF 70-200mm f/2.8L IS USM Z", "qty_1",
     "Telephoto zoom, constant f/2.8\n"
     "Internal zoom, 5.5-stop IS\n"
     "Compatible w/ RF 1.4x & 2x extenders",
     430000),
    # ── 3. Drone & 360 ──
    ("3. Drone & 360\u00b0", "DJI Mavic 4 Pro (RC 2)", "qty_2",
     "100MP 4/3\" Hasselblad CMOS\n"
     "6K60fps / 4K120fps 10-bit\n"
     "51min flight, omnidirectional sensing",
     535000),
    ("3. Drone & 360\u00b0", "Insta360 X5", "qty_2",
     "8K30fps 360 video, 72MP photos\n"
     "Dual 1/1.28\" sensors\n"
     "Waterproof 49ft, FlowState stabilization",
     63250),
    # ── 4. Audio ──
    ("4. Audio", "Rode NTG5 Shotgun Microphone", "qty_1",
     "Moisture-resistant short shotgun\n"
     "For film, TV & documentaries\n"
     "Phantom powered, shockmount + windshield",
     64400),
    # ── 6. Storage ──
    ("6. Storage", "Lexar 1TB Silver Plus microSD (UHS-I)", "qty_2",
     "1TB UHS-I microSDXC, 205MB/s read\n"
     "V30/U3/A2, 4K video rated\n"
     "For DJI Mavic 4 Pro & Insta360 X5",
     38000),
    ("6. Storage", "SanDisk Extreme Pro 4TB SSD (USB 4)", "qty_3",
     "Read 3800MB/s, USB4 / TB4\n"
     "IP65 rated, 2m drop protection\n"
     "Field offload hub",
     62160),
    ("6. Storage", "Lexar Gold CFexpress Type B 512GB", "qty_4",
     "Read 3600MB/s, PCIe Gen 4\n"
     "8K raw video rated, VPG400\n"
     "For Canon R5 II 8K60 Raw",
     28500),
    ("6. Storage", "5TB External Hard Drive", "qty_1",
     "5TB capacity, USB 3.x\n"
     "Mass archival of RAW footage\n"
     "Physical backup storage",
     25000),
    ("6. Storage", "64GB USB Thumbdrive", "qty_1",
     "USB 3.x, quick transfers\n"
     "Field delivery of preview files",
     1200),
    ("6. Storage", "1TB USB Thumbdrive", "qty_1",
     "1TB USB 3.x high-capacity\n"
     "Portable client deliverables",
     12000),
    # ── 7. Gimbals, Support & Power ──
    ("7. Gimbals, Support & Power", "DJI RS 5 Pro Gimbal Combo", "qty_1",
     "3-axis, ActiveTrack Pro\n"
     "3kg payload, LiDAR AF\n"
     "OLED touchscreen, native vertical",
     92000),
    ("7. Gimbals, Support & Power", "SmallRig x Potato Jet TRIBEX Tripod", "qty_1",
     "Carbon fiber, X-Clutch hydraulic legs\n"
     "Fluid head, 4-step counterbalance\n"
     "13.2 lb payload, 66.1\" max height",
     121000),
    ("7. Gimbals, Support & Power", "Canon LP-E6NH Battery", "qty_4",
     "2130mAh rechargeable Li-Ion\n"
     "For Canon EOS R5 Mark II\n"
     "6A max continuous discharge",
     20000),
    ("7. Gimbals, Support & Power", "Canon Speedlight V100 (Godox V100 Pro)", "qty_2",
     "100W output, round head design\n"
     "1.7s recycle, USB-C charging\n"
     "2.4G wireless X system, E-TTL II",
     35000),
     ("7. Gimbals, Support & Power", "Canon BG-R20 Battery Grip", "qty_1",
     "Dual LP-E6P/NH battery operation\n"
     "Vertical shooting grip & controls\n"
     "Weather-sealed",
     65000),
     ("7. Gimbals, Support & Power", "USB to DC Barrel Jack Power Connector", "qty_1",
      "Camera DC power input\n"
      "Runs cameras from power station\n"
      "Continuous field recording",
      2500),
    # ── 8. Bags & Transport ──
    ("8. Bags & Transport", "Camera Bag", "qty_1",
     "Padded compartments for bodies & lenses\n"
     "Weather-resistant, tripod strap",
     15000),
    ("8. Bags & Transport", "Gear Backpack", "qty_1",
     "General transport of gear\n"
     "Large capacity, laptop sleeve",
     12000),
    ("8. Bags & Transport", "Laptop Bag", "qty_1",
     "For MacBook M5 Pro 14\"\n"
     "Padded, cable & drive pockets",
     8000),
    # ── 9. Peripherals & Cables ──
    ("9. Peripherals & Cables", "Ugreen CM681 11-in-1 USB-C Dock", "qty_3",
     "HDMI, USB-A/C, SD/TF, Ethernet\n"
     "PD 100W pass-through\n"
     "Field & studio connectivity",
     10000),
    ("9. Peripherals & Cables", "SanDisk Extreme Pro CFexpress Card Reader", "qty_1",
     "USB 3.2 Gen 2, 1700MB/s\n"
     "Fast CFexpress offload",
     6500),
    ("9. Peripherals & Cables", "Wired Type-C Mouse", "qty_2",
     "USB-C, quiet click\n"
     "Compatible with MacBook",
     1000),
    ("9. Peripherals & Cables", "Mouse Pad", "qty_2",
     "Non-slip rubber base\n"
     "Large surface",
     500),
    ("9. Peripherals & Cables", "JBL Quantum 300 Gaming Headphones", "qty_4",
     "Over-ear, detachable mic\n"
     "USB wired, JBL Quantum Sound\n"
     "Monitoring & editing",
     10000),
    ("9. Peripherals & Cables", "USB Type-C to Type-C Cable", "qty_3",
     "USB-C 3.2, data + power\n"
     "For drives, docks & devices",
     1500),
    # ── 10. Crew ──
    ("10. Crew Services", "Camera Crew", "qty_1",
     "Camera crew services\n"
     "Operator + assistant\n"
     "Per project",
     27000),
]

# (category, name, billing, details, amount_ksh_base)
SUBS = [
    ("Creative Software", "Artlist Max", "Per 6 months",
     f"$39.99/mo\n(KSh {int(39.99*USD_RATE*6):,}/6mo)\nMusic, SFX, stock video", 39.99 * USD_RATE * 6),
    ("Creative Software", "Postshot (Indie)", "Per 6 months",
     f"EUR 17/mo\n(KSh {int(17*USD_RATE*6):,}/6mo)\n3D gaussian splatting\nRealityCapture: FREE (under $1M revenue)", 17 * USD_RATE * 6),
    ("Creative Software", "PlayCanvas + SuperSplat", "Per 6 months",
     f"$15/mo\n(KSh {int(15*USD_RATE*6):,}/6mo)\nWeb 3D + gaussian splat editor\nSuperSplat included free", 15 * USD_RATE * 6),
    ("Creative Software", "Spline (Starter)", "Per 6 months",
     f"$15/mo\n(KSh {int(15*USD_RATE*6):,}/6mo)\nInteractive 3D web design\nThree.js: free, open source", 15 * USD_RATE * 6),
    ("Creative Software", "Topaz Video AI (Personal)", "Per 6 months",
     f"$33/mo\n(KSh {int(33*USD_RATE*6):,}/6mo)\nAI upscale to 4K/8K, denoise, stabilize", 33 * USD_RATE * 6),
    ("Creative Software", "DaVinci Resolve Studio", "One-time",
     f"$295 one-time\n(KSh {int(295*USD_RATE):,})\nPro color grading, editing, Fusion, Fairlight", 295 * USD_RATE),
    ("AI & Infrastructure", "Vast AI", "Per 6 months",
     f"$250 USD\n(KSh {int(250*USD_RATE):,}/6mo)\nGPU cloud for gsplat/rendering", 250 * USD_RATE),
    ("AI & Infrastructure", "OpenRouter", "Per 6 months",
     f"$500 USD\n(KSh {int(500*USD_RATE):,}/6mo)\nAI model API access", 500 * USD_RATE),
    ("AI & Infrastructure", "NanoBanana Pro", "Per 6 months",
     f"$39.99/mo\n(KSh {int(39.99*USD_RATE*6):,}/6mo)\nImage-to-video AI, 4K, commercial license", 39.99 * USD_RATE * 6),
    ("AI & Infrastructure", "RunPod GPU (FastAPI + Celery)", "Pay-as-you-go",
     f"~$120/mo est.\n(KSh {int(120*USD_RATE*6):,}/6mo)\nGPU server (A100 ~$1.19/hr)\nBilled per second, usage-based", 120 * USD_RATE * 6),
    ("Cloud Storage & Hosting", "Google Drive (Google One 5TB)", "Per 6 months",
     f"$24.99/mo\n(KSh {int(24.99*USD_RATE*6):,}/6mo)\n5TB cloud storage", 24.99 * USD_RATE * 6),
    ("Cloud Storage & Hosting", "DataOcean Droplet", "Per 6 months",
     "KSh 18,650/6mo\nCloud server hosting", 18650),
    ("Cloud Storage & Hosting", "Cloudflare Pro", "Per 6 months",
     "KSh 15,550/6mo\nCDN, DNS, DDoS protection", 15550),
    ("Office Connectivity", "Safaricom Office WiFi", "Per month",
     "KSh 8,000/mo\nOffice internet connection", 8000),
]


class DocPDF(FPDF):
    def header(self):
        self.set_font("Helvetica", "B", 16)
        self.cell(0, 10, self.title, align="C", new_x="LMARGIN", new_y="NEXT")
        self.set_font("Helvetica", "", 9)
        self.cell(0, 6, self.subtitle, align="C", new_x="LMARGIN", new_y="NEXT")
        self.ln(4)
        self.set_draw_color(200, 30, 30)
        self.set_line_width(0.5)
        self.line(10, self.get_y(), 200, self.get_y())
        self.ln(4)

    def footer(self):
        self.set_y(-15)
        self.set_font("Helvetica", "I", 8)
        self.cell(0, 10, f"Page {self.page_no()}/{{nb}}  |  KICC Platform  |  Prices subject to change", align="C")

    def category_header(self, col_w, category):
        self.set_font("Helvetica", "B", 9)
        self.set_fill_color(220, 230, 240)
        self.cell(sum(col_w), 7, f"  {category}", border=1, fill=True, new_x="LMARGIN", new_y="NEXT")
        self.ln(1)

    def equipment_table(self):
        col_w = [50, 12, 68, 22, 22, 22]
        self.set_font("Helvetica", "B", 8)
        headers = ["Item", "Qty", "Key Specifications", "Unit Price\n(KSh)", "Total\n(KSh)", "Total +16% VAT\n(KSh)"]
        for i, h in enumerate(headers):
            self.cell(col_w[i], 10, h, border=1, align="C")
        self.ln()

        last_cat = None
        for name, qty_key, spec, unit_price, *_ in ITEMS:
            pass
        # re-iterate with category
        last_cat = None
        for cat, name, qty_key, spec, unit_price in ITEMS:
            if cat != last_cat:
                self.category_header(col_w, cat)
                last_cat = cat
            qty = int(qty_key.split("_")[1])
            adj = int(unit_price * TX_COST)
            total = adj * qty
            total_vat = int(total * 1.16)
            spec_lines = spec.count("\n") + 1
            row_h = max(6 * spec_lines + 2, 8)
            y0 = self.get_y()
            x0 = self.get_x()
            if y0 + row_h > 270:
                self.add_page()
                y0 = self.get_y()

            self.set_font("Helvetica", "B", 7)
            self.multi_cell(col_w[0], 6, name, border="LTB", new_x="RIGHT")
            self.set_y(y0); self.set_x(x0 + col_w[0])

            self.set_font("Helvetica", "", 9)
            self.cell(col_w[1], row_h, str(qty), border=1, align="C")
            self.set_x(x0 + col_w[0] + col_w[1])

            self.set_font("Helvetica", "", 6.5)
            ys = self.get_y()
            self.multi_cell(col_w[2], 6, spec, border="LTB", new_x="RIGHT")
            se = self.get_y()
            self.set_y(y0); self.set_x(x0 + col_w[0] + col_w[1] + col_w[2])

            self.set_font("Courier", "", 8)
            self.cell(col_w[3], row_h, f"{adj:,}", border=1, align="R")
            self.set_x(x0 + col_w[0] + col_w[1] + col_w[2] + col_w[3])
            self.cell(col_w[4], row_h, f"{total:,}", border=1, align="R")
            self.set_x(x0 + col_w[0] + col_w[1] + col_w[2] + col_w[3] + col_w[4])
            self.set_font("Courier", "B", 8)
            self.cell(col_w[5], row_h, f"{total_vat:,}", border=1, align="R")
            self.set_y(max(y0 + row_h, se))
        self.ln(4)

        eq_total = sum(int(item[4] * TX_COST) * int(item[2].split("_")[1]) for item in ITEMS)
        eq_vat = int(eq_total * 0.16)
        eq_grand = eq_total + eq_vat

        self.set_font("Helvetica", "", 9)
        cw = [130, 50]
        rows = [
            ("Subtotal (excl. VAT, +5% tx)", f"KSh {eq_total:,}"),
            ("VAT (16%)", f"KSh {eq_vat:,}"),
            ("TOTAL (incl. VAT)", f"KSh {eq_grand:,}"),
        ]
        for label, val in rows:
            bold = "TOTAL" in label and "VAT" not in label
            self.set_font("Helvetica", "B" if bold else "", 9)
            self.cell(cw[0], 7, label, align="R")
            self.set_font("Courier", "B", 9)
            self.cell(cw[1], 7, val, align="R")
            self.ln()

    def subscriptions_table(self):
        col_w = [50, 28, 54, 30, 38]
        self.set_font("Helvetica", "B", 8)
        headers = ["Service", "Billing", "Details", "Price (KSh)", "Notes"]
        for i, h in enumerate(headers):
            self.cell(col_w[i], 10, h, border=1, align="C")
        self.ln()

        last_cat = None
        for cat, name, billing, spec, amount in SUBS:
            if cat != last_cat:
                self.category_header(col_w, cat)
                last_cat = cat
            spec_lines = spec.count("\n") + 1
            row_h = max(spec_lines * 5 + 2, 12)
            y0 = self.get_y()
            x0 = self.get_x()
            if y0 + row_h > 277:
                self.add_page()
                y0 = self.get_y()

            self.set_font("Helvetica", "B", 7)
            self.cell(col_w[0], row_h, name, border=1, align="C")
            self.set_font("Helvetica", "", 7)
            self.cell(col_w[1], row_h, billing, border=1, align="C")

            ys = self.get_y()
            self.multi_cell(col_w[2], 5, spec, border="LTB", new_x="RIGHT")
            se = self.get_y()
            self.set_y(y0)
            self.set_x(x0 + col_w[0] + col_w[1] + col_w[2])

            amt = f"{int(amount * TX_COST):,}"
            self.set_font("Courier", "", 8)
            self.cell(col_w[3], row_h, amt, border=1, align="R")
            self.set_x(x0 + col_w[0] + col_w[1] + col_w[2] + col_w[3])
            self.cell(col_w[4], row_h, "", border=1, align="C")
            self.set_y(max(y0 + row_h, se))
        self.ln(4)

        sub_total = int(sum(s[4] for s in SUBS))
        sub_tx = int(sub_total * TX_COST)
        self.set_font("Helvetica", "B", 9)
        cw = [130, 50]
        self.cell(cw[0], 7, "Subscriptions Total (base)", align="R")
        self.set_font("Courier", "B", 9)
        self.cell(cw[1], 7, f"KSh {sub_total:,}", align="R")
        self.ln()
        self.set_font("Helvetica", "B", 9)
        self.cell(cw[0], 7, "Total (+5% tx)", align="R")
        self.set_font("Courier", "B", 9)
        self.cell(cw[1], 7, f"KSh {sub_tx:,}", align="R")
        self.ln()


# ── Equipment PDF ──
pdf = DocPDF(orientation="P", unit="mm", format="A4")
pdf.title = "KICC 3D Pipeline - Equipment Quote"
pdf.subtitle = "Cameras, lenses, drones, storage, support & peripherals for photogrammetry, walkthrough & VR production"
pdf.alias_nb_pages()
pdf.set_auto_page_break(auto=True, margin=20)
pdf.add_page()
pdf.equipment_table()
out_eq = "/home/kicc/Desktop/kicc/kicc-platform/KICC_Equipment_Quote.pdf"
pdf.output(out_eq)
print(f"Equipment PDF: {out_eq}")

# ── Subscriptions PDF ──
pdf2 = DocPDF(orientation="P", unit="mm", format="A4")
pdf2.title = "KICC 3D Pipeline - Subscriptions & Cloud Services"
pdf2.subtitle = "Software, AI & cloud subscriptions for photogrammetry, walkthrough & VR production"
pdf2.alias_nb_pages()
pdf2.set_auto_page_break(auto=True, margin=20)
pdf2.add_page()
pdf2.subscriptions_table()
out_sub = "/home/kicc/Desktop/kicc/kicc-platform/subs.pdf"
pdf2.output(out_sub)
print(f"Subscriptions PDF: {out_sub}")

# ── Equipment & Accessories Only PDF (no subscriptions) ──
pdf3 = DocPDF(orientation="P", unit="mm", format="A4")
pdf3.title = "KICC 3D Pipeline - Equipment & Accessories Only"
pdf3.subtitle = "Hardware quote only - cameras, lenses, drone, storage, support & peripherals"
pdf3.alias_nb_pages()
pdf3.set_auto_page_break(auto=True, margin=20)
pdf3.add_page()
pdf3.equipment_table()
out_acc = "/home/kicc/Desktop/kicc/kicc-platform/KICC_Equipment_Accessories_Only.pdf"
pdf3.output(out_acc)
print(f"Equipment-only PDF: {out_acc}")
