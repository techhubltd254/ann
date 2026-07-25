"""Sort and label Mombasa & Kilifi sector images."""

from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

INPUT_DIR = Path("m&k")
OUTPUT_DIR = Path("m&k_labeled")

FILENAME_MAP = {
    # === KILIFI ===
    "aerial_view_watamu":             ("Kilifi", "Watamu Marine Park — Coral Reefs & Turtles"),
    "swahili_stone_ruins_gede":       ("Kilifi", "Gede Ruins — Ancient Swahili City"),
    "aerial_drone_shot_pga_baobab":   ("Kilifi", "Vipingo Ridge — PGA Golf & MICE Tourism"),
    "workers_sorting_mangoes":        ("Kilifi", "Milly Fruit Processing — Agro-Industry"),
    "community_fish_farming":         ("Kilifi", "Fish Ponds — Blue Economy & Aquaculture"),
    "boardwalk_over_creek":           ("Kilifi", "Mida Creek — Mangrove Eco-Tourism"),
    "dhow_sailboat_on_kilifi":        ("Kilifi", "Kilifi Creek — Scenic Landmark & Dhows"),
    "trail_in_arabuko_sokoke":        ("Kilifi", "Arabuko Sokoke — Forest Conservation"),
    "kenyan_workers_operating":       ("Kilifi", "EPZ Factories — Industrial Jobs Boom"),
    "farmer_carrying_cashew":         ("Kilifi", "Cashew & Coconut — Agriculture Value Chains"),
    "wooden_dhow_sailing":            ("Kilifi", "Traditional Dhow — Maritime Heritage"),
    "children_learning_with_tablets": ("Kilifi", "ECDE ICT Classrooms — Digital Education"),
    "young_players_on_sports":        ("Kilifi", "Youth Sports Fields — Talent Development"),
    "women_filling_water":            ("Kilifi", "Solar Borehole — Clean Water Access"),
    "swahili_dancers_performing":     ("Kilifi", "Swahili Cultural Festival — Heritage"),

    # === MOMBASA ===
    "aerial_view_of_mombasa_port":    ("Mombasa", "Port of Mombasa — East Africa's Largest Port"),
    "fort_jesus_unesco":              ("Mombasa", "Fort Jesus — UNESCO World Heritage Site"),
    "woman_walking_in_mombasa_old":   ("Mombasa", "Old Town — Swahili Architecture & Heritage"),
    "mombasa_tusks_spanning":         ("Mombasa", "Mombasa Tusks — Iconic City Landmark"),
    "aerial_view_luxury_beach":       ("Mombasa", "Nyali Beach Resorts — Coastal Tourism"),
    "train_at_mombasa_terminus":      ("Mombasa", "SGR Madaraka Express — Rail Transport"),
    "aircraft_taxiing_at_moi":        ("Mombasa", "Moi International Airport — Aviation Gateway"),
    "giraffe_eating_from_hand":       ("Mombasa", "Haller Park — Wildlife Conservation"),
    "bustling_market_overflowing":    ("Mombasa", "Kongowea Market — Trade & Commerce"),
    "snorkelers_at_mombasa_marine":   ("Mombasa", "Marine National Park — Coral & Snorkeling"),
    "liwatori_fisheries_complex":     ("Mombasa", "Liwatoni Fisheries — Blue Economy Hub"),
    "nurses_attending_mother":        ("Mombasa", "Coast General Hospital — Healthcare"),
    "likoni_ferry_packed":            ("Mombasa", "Likoni Ferry — Daily Transport Lifeline"),
    "spice_stall_in_mombasa":         ("Mombasa", "Spice Market — Cultural Commerce"),
    "mombasa_city_skyline_cruise":    ("Mombasa", "Cruise Ship Terminal — Maritime Tourism"),
}


def find_match(stem: str) -> tuple[str, str] | None:
    stem_lower = stem.lower().replace("-", "_")
    for key, val in sorted(FILENAME_MAP.items(), key=lambda x: -len(x[0])):
        if key in stem_lower:
            return val
    return None


OUTPUT_DIR.mkdir(exist_ok=True)
count = {"Kilifi": 0, "Mombasa": 0}

for fpath in sorted(INPUT_DIR.glob("*.*")):
    if fpath.suffix.lower() not in {".jpg", ".jpeg", ".png"}:
        continue
    stem = fpath.stem
    match = find_match(stem)
    if not match:
        print(f"  SKIP (no match): {fpath.name}")
        continue

    county, label = match
    output_subdir = OUTPUT_DIR / county
    output_subdir.mkdir(exist_ok=True)

    img = Image.open(fpath).convert("RGB")
    w, h = img.size
    draw = ImageDraw.Draw(img)

    # County name (top line, bigger)
    text_county = county.upper()
    text_sector = label

    font_size = max(22, min(w, h) // 30)
    font_small = max(16, min(w, h) // 40)

    try:
        font_big = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", font_size)
        font_sml = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", font_small)
    except Exception:
        font_big = font_sml = ImageFont.load_default()

    bb1 = draw.textbbox((0, 0), text_county, font=font_big)
    bb2 = draw.textbbox((0, 0), text_sector, font=font_sml)
    tw1, th1 = bb1[2] - bb1[0], bb1[3] - bb1[1]
    tw2, th2 = bb2[2] - bb2[0], bb2[3] - bb2[1]
    pad = font_size // 2
    total_w = max(tw1, tw2) + pad * 3
    total_h = th1 + th2 + pad * 4
    bx, by = pad, h - total_h - pad - 10
    bw, bh = total_w, total_h

    overlay = Image.new("RGBA", img.size, (0, 0, 0, 0))
    overlay_draw = ImageDraw.Draw(overlay)
    overlay_draw.rounded_rectangle(
        (bx, by, bx + bw, by + bh), radius=pad // 2, fill=(0, 0, 0, 170)
    )
    img = Image.alpha_composite(img.convert("RGBA"), overlay).convert("RGB")
    draw = ImageDraw.Draw(img)

    # County name in bold white
    draw.text((bx + pad, by + pad), text_county, font=font_big, fill=(255, 255, 255))
    # Sector label in lighter/smaller
    draw.text((bx + pad, by + pad + th1 + 4), text_sector, font=font_sml, fill=(220, 220, 220))

    out_name = f"{county}_{text_sector.split('—')[0].strip().replace(' ', '_')}.jpg"
    out_path = output_subdir / out_name
    img.save(out_path, quality=95)
    count[county] += 1
    print(f"  {county:8s} -> {out_path.parent.name}/{out_name}")

total = sum(count.values())
print(f"\nDone. {total} images sorted & labeled ({count['Kilifi']} Kilifi, {count['Mombasa']} Mombasa)")
