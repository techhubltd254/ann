"""Overlay county names on profile pictures using Pillow."""

from pathlib import Path
import json
from PIL import Image, ImageDraw, ImageFont

INPUT_DIR = Path("county profile pics")
OUTPUT_DIR = Path("county profile pics labeled")
COUNTIES_JSON = Path("kenya-3d-platform/data/counties.json")

OUTPUT_DIR.mkdir(exist_ok=True)

with open(COUNTIES_JSON) as f:
    counties = json.load(f)

# Map filename keywords to county name
FILENAME_MAP = {
    "nairobi": "Nairobi City",
    "mombasa": "Mombasa",
    "diani_beach": "Kwale",
    "kwale": "Kwale",
    "watamu": "Kilifi",
    "coral_reefs": "Kilifi",
    "malindi": "Kilifi",
    "kilifi": "Kilifi",
    "tana_river_primate": "Tana River",
    "red_colobus": "Tana River",
    "tana_river": "Tana River",
    "lamu_old_town": "Lamu",
    "lamu": "Lamu",
    "tsavo": "Taita-Taveta",
    "red_elephants": "Taita-Taveta",
    "garissa": "Garissa",
    "wajir": "Wajir",
    "mandera": "Mandera",
    "marsabit_national": "Marsabit",
    "marsabit": "Marsabit",
    "isiolo": "Isiolo",
    "buffalo_springs": "Isiolo",
    "meru_national": "Meru",
    "meru": "Meru",
    "mount_kenya_peaks": "Tharaka-Nithi",
    "tharaka": "Tharaka-Nithi",
    "runyenjes": "Embu",
    "embu": "Embu",
    "yatta_plateau": "Kitui",
    "kitui": "Kitui",
    "machakos_hills": "Machakos",
    "machakos": "Machakos",
    "kibwezi": "Makueni",
    "makueni": "Makueni",
    "aberdare_national_park": "Nyandarua",
    "aberdare": "Nyandarua",
    "nyandarua": "Nyandarua",
    "mount_kenya_from_nyeri": "Nyeri",
    "nyeri": "Nyeri",
    "mount_kenya_from_rice": "Kirinyaga",
    "kirinyaga": "Kirinyaga",
    "mwea": "Kirinyaga",
    "mukurwe_wa_nyagathanga": "Murang'a",
    "muranga": "Murang'a",
    "murang'a": "Murang'a",
    "limuru_tea": "Kiambu",
    "kiambu": "Kiambu",
    "lake_turkana": "Turkana",
    "turkana": "Turkana",
    "cherangany": "West Pokot",
    "west_pokot": "West Pokot",
    "samburu_warriors": "Samburu",
    "samburu": "Samburu",
    "kitale_sugarcane": "Trans Nzoia",
    "trans_nzoia": "Trans Nzoia",
    "trans-nzoia": "Trans Nzoia",
    "eldoret_maize": "Uasin Gishu",
    "uasin_gishu": "Uasin Gishu",
    "kerio_valley": "Elgeyo-Marakwet",
    "iten": "Elgeyo-Marakwet",
    "elgeyo": "Elgeyo-Marakwet",
    "nandi_hills": "Nandi",
    "nandi": "Nandi",
    "hippos_and_fishermen": "Baringo",
    "lake_baringo": "Baringo",
    "baringo": "Baringo",
    "rhino_sanctuary": "Laikipia",
    "ol_pejeta": "Laikipia",
    "laikipia": "Laikipia",
    "flamingoes": "Nakuru",
    "lake_nakuru": "Nakuru",
    "nakuru": "Nakuru",
    "wildebeest_migration": "Narok",
    "maasai_mara": "Narok",
    "narok": "Narok",
    "amboseli": "Kajiado",
    "kilimanjaro": "Kajiado",
    "elephants_with_kilimanjaro": "Kajiado",
    "kajiado": "Kajiado",
    "unuru_tea": "Kericho",
    "kericho": "Kericho",
    "tea_estates_maasai_mara": "Bomet",
    "bomet": "Bomet",
    "kakamega_forest": "Kakamega",
    "kakamega": "Kakamega",
    "maragoli": "Vihiga",
    "vihiga": "Vihiga",
    "mount_elgon": "Bungoma",
    "bungoma": "Bungoma",
    "busia_border": "Busia",
    "busia": "Busia",
    "lake_victoria_sunset": "Siaya",
    "siaya": "Siaya",
    "kisumu_impala": "Kisumu",
    "kisumu": "Kisumu",
    "roan_antelope": "Homa Bay",
    "ruma_national": "Homa Bay",
    "homa_bay": "Homa Bay",
    "thimlich_ohinga": "Migori",
    "migori": "Migori",
    "soapstone": "Kisii",
    "kisii": "Kisii",
    "forested_hills": "Nyamira",
    "nyamira": "Nyamira",
    "nairobi_skyline": "Nairobi City",
    "giraffes": "Nairobi City",
    "fort_jesus": "Mombasa",
    "diani_beach_shoreline": "Kwale",
}


def find_county(filename: str) -> str | None:
    stem = filename.rsplit(".", 1)[0].lower().replace("-", "_")
    for key, county_name in sorted(FILENAME_MAP.items(), key=lambda x: -len(x[0])):
        if key in stem:
            return county_name
    return None


processed_names = set()
files = sorted(INPUT_DIR.glob("*.*"))

for fpath in files:
    if fpath.suffix.lower() not in {".jpg", ".jpeg", ".png", ".webp"}:
        continue
    county_name = find_county(fpath.name)
    if not county_name:
        print(f"  SKIP (no match): {fpath.name}")
        continue
    if county_name in processed_names:
        continue
    processed_names.add(county_name)

    img = Image.open(fpath).convert("RGB")
    w, h = img.size
    draw = ImageDraw.Draw(img)

    text = county_name.upper()
    font_size = max(28, min(w, h) // 25)
    try:
        font = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", font_size)
    except Exception:
        font = ImageFont.load_default()

    bbox = draw.textbbox((0, 0), text, font=font)
    tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
    pad = font_size // 2
    bx, by = pad, h - th - pad * 4
    bw, bh = tw + pad * 3, th + pad * 2

    overlay = Image.new("RGBA", img.size, (0, 0, 0, 0))
    overlay_draw = ImageDraw.Draw(overlay)
    overlay_draw.rounded_rectangle(
        (bx, by, bx + bw, by + bh), radius=pad // 2, fill=(0, 0, 0, 160)
    )
    img = Image.alpha_composite(img.convert("RGBA"), overlay).convert("RGB")
    draw = ImageDraw.Draw(img)

    tx = bx + pad
    ty = by + pad + 2
    draw.text((tx, ty), text, font=font, fill=(255, 255, 255))

    out_path = OUTPUT_DIR / f"{county_name.replace(' ', '_')}.jpg"
    img.save(out_path, quality=95)
    print(f"  {county_name:25s} <- {fpath.name}")

print(f"\nDone. {len(processed_names)}/47 county images saved to {OUTPUT_DIR}/")
missing = set(c["name"] for c in counties) - processed_names
if missing:
    print(f"Missing: {', '.join(sorted(missing))}")
