#!/usr/bin/env python3
"""Generate comprehensive KICC Credential Map PDF."""
import json, os
from fpdf import FPDF

pdf = FPDF()
pdf.add_page()
pdf.set_font("Helvetica", "B", 20)
pdf.cell(0, 14, "KICC PLATFORM - COMPREHENSIVE CREDENTIAL MAP", align="C")
pdf.ln(18)

pdf.set_font("Helvetica", "", 9)
pdf.cell(0, 5, "Generated: 2026-09-23  |  80 credential slots across 6 categories + 20 algorithms + 9 agency sources", align="C")
pdf.ln(10)

pdf.set_font("Helvetica", "B", 12)
pdf.cell(0, 8, "HOW TO GO LIVE (3 steps)", align="L")
pdf.ln(6)
pdf.set_font("Helvetica", "", 10)
steps = [
    "1. Register at each provider below and obtain API keys",
    "2. Fill them into .env.integration (copy of .env.example)",
    "3. Set MOCK_MODE=false and restart the integration service",
]
for s in steps:
    pdf.cell(0, 5, s)
    pdf.ln(4)
pdf.ln(4)

# Category colors
CAT_COLORS = {
    "Payments": (0, 102, 204),
    "Freight": (0, 153, 76),
    "Customs": (153, 102, 51),
    "Identity": (102, 51, 153),
    "FX": (204, 102, 0),
    "Cloudflare": (255, 153, 0),
    "Desk Systems": (102, 102, 102),
    "Database": (0, 0, 0),
}

data = {
    "Payments": [
        ("STRIPE_SECRET_KEY", "sk_live_*", "Stripe Dashboard > API Keys", "https://dashboard.stripe.com/apikeys"),
        ("STRIPE_PUBLISHABLE_KEY", "pk_live_*", "Stripe Dashboard > API Keys", "https://dashboard.stripe.com/apikeys"),
        ("STRIPE_WEBHOOK_SECRET", "whsec_*", "Stripe Dashboard > Webhooks", "https://dashboard.stripe.com/webhooks"),
        ("FLUTTERWAVE_SECRET_KEY", "FLWSECK-*", "Flutterwave Dashboard > Settings > API", "https://dashboard.flutterwave.com/settings/keys"),
        ("FLUTTERWAVE_PUBLIC_KEY", "FLWPUBK-*", "Flutterwave Dashboard > Settings > API", "https://dashboard.flutterwave.com/settings/keys"),
        ("FLUTTERWAVE_SECRET_HASH", "any string", "Flutterwave Dashboard > Webhooks", "https://dashboard.flutterwave.com/settings/webhooks"),
        ("PAYSTACK_SECRET_KEY", "sk_live_*", "Paystack Dashboard > Settings > API", "https://dashboard.paystack.com/#/settings/developer"),
        ("PAYSTACK_PUBLIC_KEY", "pk_live_*", "Paystack Dashboard > Settings > API", "https://dashboard.paystack.com/#/settings/developer"),
        ("MPESA_CONSUMER_KEY", "", "Safaricom Daraja Portal > App", "https://developer.safaricom.co.ke"),
        ("MPESA_CONSUMER_SECRET", "", "Safaricom Daraja Portal > App", "https://developer.safaricom.co.ke"),
        ("MPESA_SHORTCODE", "174379", "Safaricom (provided on onboarding)", "https://developer.safaricom.co.ke"),
        ("MPESA_PASSKEY", "", "Safaricom Daraja Portal > App", "https://developer.safaricom.co.ke"),
        ("MPESA_INITIATOR_NAME", "", "Safaricom (provided on B2C onboarding)", "https://developer.safaricom.co.ke"),
        ("MPESA_SECURITY_CREDENTIAL", "", "Safaricom (generated from certificate)", "https://developer.safaricom.co.ke"),
        ("PESAPAL_CONSUMER_KEY", "", "Pesapal Dashboard > API Keys", "https://pay.pesapal.com"),
        ("PESAPAL_CONSUMER_SECRET", "", "Pesapal Dashboard > API Keys", "https://pay.pesapal.com"),
        ("BANK_NAME", "", "Your corporate bank", "N/A"),
        ("BANK_CLIENT_ID", "", "Bank API onboarding desk", "N/A"),
        ("BANK_CLIENT_SECRET", "", "Bank API onboarding desk", "N/A"),
        ("BANK_ACCOUNT_NUMBER", "", "Your bank account", "N/A"),
        ("BANK_ESCROW_ACCOUNT_REF", "", "Bank escrow account ref", "N/A"),
        ("BANK_COLLECTION_ACCOUNT", "", "Bank collection account", "N/A"),
    ],
    "Freight": [
        ("DHL_API_KEY", "", "DHL Developer Portal > MyDHL API", "https://developer.dhl.com"),
        ("DHL_API_SECRET", "", "DHL Developer Portal > MyDHL API", "https://developer.dhl.com"),
        ("DHL_ACCOUNT_NUMBER", "", "DHL Express account number", "N/A"),
        ("FEDEX_CLIENT_ID", "", "FedEx Developer Portal", "https://developer.fedex.com"),
        ("FEDEX_CLIENT_SECRET", "", "FedEx Developer Portal", "https://developer.fedex.com"),
        ("FEDEX_ACCOUNT_NUMBER", "", "FedEx account number", "N/A"),
        ("ARAMEX_USERNAME", "", "Aramex Developer Centre", "https://www.aramex.com"),
        ("ARAMEX_PASSWORD", "", "Aramex Developer Centre", "https://www.aramex.com"),
        ("ARAMEX_ACCOUNT_NUMBER", "", "Aramex account number", "N/A"),
        ("ARAMEX_ACCOUNT_PIN", "", "Aramex account PIN", "N/A"),
        ("ARAMEX_ACCOUNT_ENTITY", "", "Aramex entity code", "N/A"),
        ("SENDY_API_KEY", "", "Sendy Dashboard > Developer", "https://sendyit.com"),
        ("POSTA_API_KEY", "", "Postal Corporation of Kenya API onboarding", "N/A"),
        ("POSTA_API_BASE", "", "Posta Kenya API base URL", "N/A"),
        ("OCEAN_CLIENT_ID", "", "Maersk/Hapag-Lloyd Developer Portal", "https://developer.maersk.com"),
        ("OCEAN_CLIENT_SECRET", "", "Maersk/Hapag-Lloyd Developer Portal", "https://developer.maersk.com"),
        ("OCEAN_CONSUMER_KEY", "", "Maersk/Hapag-Lloyd", "https://developer.maersk.com"),
        ("OCEAN_CONTRACT_REF", "", "Ocean freight contract reference", "N/A"),
        ("OCEAN_WEBHOOK_SECRET", "", "Ocean carrier webhook secret", "N/A"),
    ],
    "Customs": [
        ("KRA_ICMS_CLIENT_ID", "", "KRA iCMS onboarding", "https://icms.kra.go.ke"),
        ("KRA_ICMS_CLIENT_SECRET", "", "KRA iCMS onboarding", "https://icms.kra.go.ke"),
        ("KRA_ICMS_ESERVICE", "", "KRA iCMS service code", "https://icms.kra.go.ke"),
        ("KEBS_CLIENT_ID", "", "KEBS API onboarding", "https://www.kebs.org"),
        ("KEBS_CLIENT_SECRET", "", "KEBS API onboarding", "https://www.kebs.org"),
        ("EPC_CLIENT_ID", "", "Export Promotion Council", "https://epc.go.ke"),
        ("EPC_CLIENT_SECRET", "", "Export Promotion Council", "https://epc.go.ke"),
    ],
    "Identity": [
        ("ARDHISASA_CLIENT_ID", "", "Ardhisasa developer portal", "https://ardhisasa.lands.go.ke"),
        ("ARDHISASA_CLIENT_SECRET", "", "Ardhisasa developer portal", "https://ardhisasa.lands.go.ke"),
        ("ETIMS_CLIENT_ID", "", "KRA eTIMS onboarding", "https://itax.kra.go.ke"),
        ("ETIMS_CLIENT_SECRET", "", "KRA eTIMS onboarding", "https://itax.kra.go.ke"),
        ("ETIMS_PIN", "", "Business KRA PIN", "N/A"),
        ("AT_API_KEY", "", "Africa's Talking Dashboard", "https://account.africastalking.com"),
        ("AT_USERNAME", "", "Africa's Talking Dashboard", "https://account.africastalking.com"),
        ("WHATSAPP_TOKEN", "", "Meta WhatsApp Cloud API", "https://developers.facebook.com"),
        ("WHATSAPP_PHONE_ID", "", "Meta WhatsApp Cloud API", "https://developers.facebook.com"),
    ],
    "FX": [
        ("FX_CBK_RATES_URL", "", "CBK published rates feed", "https://www.centralbank.go.ke/rates"),
        ("FX_BANK_RATE_URL", "", "Corporate bank rate feed", "N/A"),
        ("FX_BANK_CLIENT_ID", "", "Bank rate feed credentials", "N/A"),
        ("FX_BANK_CLIENT_SECRET", "", "Bank rate feed credentials", "N/A"),
    ],
}

providers_data = [
    ("Payments", data["Payments"]),
    ("Freight", data["Freight"]),
    ("Customs & Trade Documentation", data["Customs"]),
    ("Identity, eTIMS & Notifications", data["Identity"]),
    ("FX Rates", data["FX"]),
]

for cat_name, items in providers_data:
    color = CAT_COLORS.get(cat_name.split()[0], (0, 0, 0))
    pdf.set_fill_color(*color)
    pdf.set_text_color(255, 255, 255)
    pdf.set_font("Helvetica", "B", 11)
    pdf.cell(0, 8, f"  {cat_name} ({len(items)} variables)", fill=True)
    pdf.ln(8)
    pdf.set_text_color(0, 0, 0)
    pdf.set_font("Helvetica", "B", 7)
    pdf.cell(50, 5, "Variable", 1)
    pdf.cell(40, 5, "Example Value", 1)
    pdf.cell(55, 5, "Where to Get It", 1)
    pdf.cell(45, 5, "Source URL", 1)
    pdf.ln()
    pdf.set_font("Helvetica", "", 7)
    for k, v, src, url in items:
        pdf.cell(50, 4, k, 1)
        pdf.cell(40, 4, v[:20] if v else "(blank)", 1)
        pdf.cell(55, 4, src[:30], 1)
        pdf.cell(45, 4, url[:30], 1)
        pdf.ln()
    pdf.ln(3)

# Algorithm page
pdf.add_page()
pdf.set_fill_color(0, 102, 204)
pdf.set_text_color(255, 255, 255)
pdf.set_font("Helvetica", "B", 12)
pdf.cell(0, 8, "  PYTHON ALGORITHMS SERVICE (port 8400) - 20 algorithms", fill=True)
pdf.ln(10)
pdf.set_text_color(0, 0, 0)
pdf.set_font("Helvetica", "B", 7)
pdf.cell(8, 5, "#", 1)
pdf.cell(40, 5, "Algorithm", 1)
pdf.cell(35, 5, "File", 1)
pdf.cell(90, 5, "Purpose", 1)
pdf.ln()
algo_list = [
    (1, "StatsAggregator", "display.py", "O(1) cached platform stats aggregator"),
    (2, "FeaturedSelector", "display.py", "Selects featured exhibitions for homepage"),
    (3, "TrustScorer (grade)", "trust.py", "Escrow trust grades A/B/C/D/F"),
    (4, "TrustScorer (dispute)", "trust.py", "Adverse dispute rate per seller"),
    (5, "VendorScorer", "vendors.py", "Composite vendor score 0-100"),
    (6, "DynamicPricer", "pricing.py", "Demand-responsive pricing multiplier"),
    (7, "Analytics", "analytics.py", "Revenue forecast & ratio"),
    (8, "Billing", "billing.py", "VAT-inclusive invoicing (16%)"),
    (9, "Recommender", "recommendations.py", "Content-based recommendations"),
    (10, "Correlation", "correlation.py", "Completeness & sector affinity"),
    (11, "MpesaSettlement", "payments.py", "M-Pesa callback settlement"),
    (12, "QualityScorer", "quality.py", "Pool distribution quality score"),
    (13, "KybRegistry", "kyb.py", "Know-Your-Business tier: T0/T1/T2"),
    (14, "SponsorshipGraph", "sponsorship.py", "Sponsor referral engine"),
    (15, "CountyClassifier", "county.py", "RPS/FNS quadrant classification"),
    (16, "PoolEngine", "pool.py", "Mother Pool distribution engine"),
    (17, "Screener", "screening.py", "Sanctions/PEP fuzzy match screening"),
    (18, "ItineraryComposer", "itinerary.py", "Tour itinerary cost composition"),
    (19, "WeatherIngestor", "weather.py", "Seasonal weather station ingestion"),
    (20, "KAnonymizer", "anonymizer.py", "k-anonymity anonymization"),
]
pdf.set_font("Helvetica", "", 7)
for num, name, file, purpose in algo_list:
    pdf.cell(8, 4, str(num), 1)
    pdf.cell(40, 4, name, 1)
    pdf.cell(35, 4, file, 1)
    pdf.cell(90, 4, purpose, 1)
    pdf.ln()
pdf.ln(5)

# Cloudflare + Infrastructure
pdf.set_fill_color(255, 153, 0)
pdf.set_text_color(255, 255, 255)
pdf.set_font("Helvetica", "B", 11)
pdf.cell(0, 8, "  CLOUDFLARE + INFRASTRUCTURE (7 items)", fill=True)
pdf.ln(8)
pdf.set_text_color(0, 0, 0)
infra_items = [
    ("CF_TOKEN", "Cloudflare API token (deploy worker)", "Cloudflare Dashboard > API Tokens"),
    ("CF_ACCOUNT", "Cloudflare account ID", "Cloudflare Dashboard > Overview"),
    ("CLOUDFLARE_API_TOKEN", "Cloudflare API token (cache purge)", "Cloudflare Dashboard > API Tokens"),
    ("GH_TOKEN", "GitHub deploy token", "GitHub > Settings > Developer settings > Tokens"),
    ("R2: kicc-r2-media", "R2 bucket for media CDN", "Cloudflare Dashboard > R2"),
    ("Worker: kicctest-gateway", "CDN edge worker", "Cloudflare Dashboard > Workers"),
    ("Worker: kicc-r2-media", "R2 media CDN worker", "Cloudflare Dashboard > Workers"),
]
pdf.set_font("Helvetica", "B", 7)
pdf.cell(45, 5, "Item", 1)
pdf.cell(70, 5, "Description", 1)
pdf.cell(75, 5, "Where to Get It", 1)
pdf.ln()
pdf.set_font("Helvetica", "", 7)
for item, desc, src in infra_items:
    pdf.cell(45, 4, item, 1)
    pdf.cell(70, 4, desc, 1)
    pdf.cell(75, 4, src, 1)
    pdf.ln()
pdf.ln(5)

# Agency data sources
pdf.set_fill_color(0, 153, 76)
pdf.set_text_color(255, 255, 255)
pdf.set_font("Helvetica", "B", 11)
pdf.cell(0, 8, "  AGENCY DATA SOURCES (9 agencies)", fill=True)
pdf.ln(8)
pdf.set_text_color(0, 0, 0)
agency_items = [
    ("CBK", "Central Bank of Kenya", "Licence verification, FX rates", "https://www.centralbank.go.ke"),
    ("KEPHIS", "Kenya Plant Health Inspectorate", "Export compliance, phytosanitary certs", "https://www.kephis.org"),
    ("KWS", "Kenya Wildlife Service", "Wildlife permits, conservancy data", "https://www.kws.go.ke"),
    ("IFMIS", "Integrated Financial Mgmt System", "County gov procurement data", "https://www.ifmis.go.ke"),
    ("Ardhisasa", "Ministry of Lands", "Land registry, property titles", "https://ardhisasa.lands.go.ke"),
    ("SEZA", "Special Economic Zones Authority", "SEZ data, investor registrations", "https://seza.go.ke"),
    ("IATA", "International Air Transport Assoc.", "Airline/travel accreditation", "https://www.iata.org"),
    ("TALA", "Tourism Analytics", "Kenya tourism data", "N/A"),
    ("KRA", "Kenya Revenue Authority", "Tax compliance, iCMS customs", "https://www.kra.go.ke"),
]
pdf.set_font("Helvetica", "B", 7)
pdf.cell(20, 5, "Code", 1)
pdf.cell(40, 5, "Name", 1)
pdf.cell(70, 5, "Data Provided", 1)
pdf.cell(55, 5, "Source URL", 1)
pdf.ln()
pdf.set_font("Helvetica", "", 7)
for code, name, purpose, url in agency_items:
    pdf.cell(20, 4, code, 1)
    pdf.cell(40, 4, name[:25], 1)
    pdf.cell(70, 4, purpose, 1)
    pdf.cell(55, 4, url, 1)
    pdf.ln()
pdf.ln(5)

# Desk systems
pdf.set_fill_color(102, 102, 102)
pdf.set_text_color(255, 255, 255)
pdf.set_font("Helvetica", "B", 11)
pdf.cell(0, 8, "  DESK SYSTEM INTEGRATIONS (19 partner systems)", fill=True)
pdf.ln(8)
pdf.set_text_color(0, 0, 0)
desk_items = [
    ("DESK_WEIGHBRIDGE_API_KEY", "Weighbridge (agriculture/commodities)"),
    ("DESK_MILLER_ERP_TOKEN", "Miller/processor ERP system"),
    ("DESK_LAB_LIMS_TOKEN", "Laboratory LIMS for certification"),
    ("DESK_WMS_TOKEN", "Warehouse management system"),
    ("DESK_COLDSTORE_TOKEN", "Cold storage facility system"),
    ("DESK_ASSAY_LAB_TOKEN", "Assay laboratory (minerals/gems)"),
    ("DESK_GEM_CERT_TOKEN", "Gemstone certification system"),
    ("DESK_ZONE_OPERATOR_TOKEN", "SEZ zone operator system"),
    ("DESK_BUILDING_OWNER_TOKEN", "Building/property owner system"),
    ("DESK_LODGE_GDS_TOKEN", "Lodge/global distribution system"),
    ("DESK_VENUE_CALENDAR_TOKEN", "Venue calendar/booking system"),
    ("DESK_HOSPITAL_SYSTEM_TOKEN", "Hospital/healthcare system"),
    ("DESK_TRAINING_PROVIDER_TOKEN", "Training provider / LMS"),
    ("DESK_RECYCLER_TOKEN", "Recycler/processor system"),
    ("DESK_PROFESSIONAL_BODY_TOKEN", "Professional body registry"),
    ("DESK_WAREHOUSE_TOKEN", "Warehouse receipt system"),
    ("DESK_BPO_SITE_TOKEN", "BPO/outsourcing site system"),
    ("DESK_COUNTY_FINANCE_TOKEN", "County government finance system"),
    ("DESK_SEZ_AUTHORITY_TOKEN", "SEZ authority system"),
]
pdf.set_font("Helvetica", "B", 7)
pdf.cell(55, 5, "Variable", 1)
pdf.cell(135, 5, "Partner System", 1)
pdf.ln()
pdf.set_font("Helvetica", "", 7)
for k, desc in desk_items:
    pdf.cell(55, 4, k, 1)
    pdf.cell(135, 4, desc, 1)
    pdf.ln()
pdf.ln(5)

# Quick-start checklist
pdf.add_page()
pdf.set_fill_color(0, 153, 0)
pdf.set_text_color(255, 255, 255)
pdf.set_font("Helvetica", "B", 14)
pdf.cell(0, 10, "  GO-LIVE CHECKLIST", fill=True)
pdf.ln(12)
pdf.set_text_color(0, 0, 0)
pdf.set_font("Helvetica", "", 9)
items = [
    "[ ] 1. Register for M-Pesa Daraja sandbox keys (developer.safaricom.co.ke)",
    "[ ] 2. Register for Stripe account & API keys (dashboard.stripe.com)",
    "[ ] 3. Register for DHL MyDHL API test keys (developer.dhl.com)",
    "[ ] 4. Register for KRA iCMS sandbox access (icms.kra.go.ke)",
    "[ ] 5. Register for Ardhisasa API access (ardhisasa.lands.go.ke)",
    "[ ] 6. Register for Africa's Talking SMS API (account.africastalking.com)",
    "[ ] 7. Register for FedEx developer portal (developer.fedex.com)",
    "[ ] 8. Register for Flutterwave/Paystack sandbox keys",
    "[ ] 9. Confirm bank API onboarding & escrow account",
    "[ ] 10. Set MOCK_MODE=false in .env.integration",
    "[ ] 11. Run: node integrations-service/run/verify_endpoints.mjs --probe",
    "[ ] 12. Set KICC_INTEGRATION_WEBHOOK_SECRET to a strong random string",
    "[ ] 13. Run: php artisan kicc:integration:health",
    "[ ] 14. Verify all 20 algorithms respond on port 8400",
    "[ ] 15. Verify all 14 webhook routes respond on port 8787",
    "[ ] 16. Run: node integrations-service/run/run_all_87.mjs (87 pipelines)",
    "[ ] 17. Update deploy.sh with production paths",
    "[ ] 18. Set up cron for restart-integration-service.sh and restart-algorithms-service.sh",
]
for item in items:
    pdf.cell(0, 6, item)
    pdf.ln()

os.makedirs("/home/kicc/Desktop/kicc/kicc-platform/storage/app", exist_ok=True)
path = "/home/kicc/Desktop/kicc/kicc-platform/storage/app/kicc-credential-map.pdf"
pdf.output(path)
print(f"PDF generated: {path}")
print(f"Pages: {pdf.pages_count}")
print(f"Credential vars: 80 across 6 categories")
print(f"Algorithms: 20")
print(f"Agency sources: 9")
print(f"Desk systems: 19")