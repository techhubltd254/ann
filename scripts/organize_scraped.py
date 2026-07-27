"""
Reorganizes scraped county data into:
  scraped_data/
    {county-name}/
      info.json          — description, contacts, stats, source URL
      sectors.json       — list of sectors found on the site
      executive.json     — governor, deputy, leadership info
      images/
        hero/            — hero/banner images
        gallery/         — gallery images
        
For all 47 counties (including 6 that failed — marked with a note)
"""

import json, os, shutil, sys, re, hashlib, urllib.request
from pathlib import Path

BASE = Path('/home/kicc/Desktop/kicc/kicc-platform')
SCRAPED_JSON = BASE / 'database' / 'data' / 'counties_scraped.json'
SCRAPED_IMAGES = BASE / 'scripts' / 'scraped_data' / 'images'
OUTPUT = BASE / 'scraped_data'

# The 47 counties with their slugs
COUNTIES = {
    'Mombasa', 'Kwale', 'Kilifi', 'Tana River', 'Lamu', 'Taita-Taveta',
    'Garissa', 'Wajir', 'Mandera', 'Marsabit', 'Isiolo', 'Meru',
    'Tharaka-Nithi', 'Embu', 'Kitui', 'Machakos', 'Makueni', 'Nyandarua',
    'Nyeri', 'Kirinyaga', "Murang'a", 'Kiambu', 'Turkana', 'West Pokot',
    'Samburu', 'Trans Nzoia', 'Uasin Gishu', 'Elgeyo-Marakwet', 'Nandi',
    'Baringo', 'Laikipia', 'Nakuru', 'Narok', 'Kajiado', 'Kericho',
    'Bomet', 'Kakamega', 'Vihiga', 'Busia', 'Siaya', 'Kisumu',
    'Homa Bay', 'Migori', 'Kisii', 'Nyamira', 'Nairobi City', 'Bungoma'
}

if not SCRAPED_JSON.exists():
    print(f"Error: scraped data not found at {SCRAPED_JSON}")
    sys.exit(1)

with open(SCRAPED_JSON) as f:
    scraped = json.load(f)

# Build lookup
scraped_map = {c['name']: c for c in scraped}

OUTPUT.mkdir(parents=True, exist_ok=True)
total_imgs = 0

for name in sorted(COUNTIES):
    slug = name.lower().replace("'", '').replace(' ', '-').replace('&', 'and')
    dir_name = name.lower().replace("'", '').replace(' ', '_').replace('&', 'and')
    county_dir = OUTPUT / dir_name
    images_dir = county_dir / 'images'
    hero_dir = images_dir / 'hero'
    gallery_dir = images_dir / 'gallery'
    
    county_dir.mkdir(parents=True, exist_ok=True)
    hero_dir.mkdir(parents=True, exist_ok=True)
    gallery_dir.mkdir(parents=True, exist_ok=True)
    
    data = scraped_map.get(name)
    
    if not data:
        # Failed county — create minimal stub
        (county_dir / 'info.json').write_text(json.dumps({
            'name': name, 'slug': slug, 'status': 'not_scraped',
            'note': 'Website was unreachable during scraping. Manual data entry required.',
            'source_url': '',
            'scraped_at': None,
            'title': '', 'description': '',
            'hero_images': [], 'gallery_images': [],
            'contacts': {}, 'executive': [], 'pages': {},
            'stats': {},
        }, indent=2))
        (county_dir / 'sectors.json').write_text('[]')
        (county_dir / 'executive.json').write_text('[]')
        print(f"  {name:25s} ✗ NOT SCRAPED (stub created)")
        continue
    
    # ─── info.json ──────────────────────────────────────────────
    info = {
        'name': name,
        'slug': slug,
        'status': 'scraped',
        'source_url': data.get('source_url') or data.get('url', ''),
        'final_url': data.get('final_url', ''),
        'scraped_at': data.get('scraped_at'),
        'title': data.get('title', ''),
        'description': data.get('description', ''),
        'contacts': data.get('contacts', {}),
        'pages': data.get('pages', {}),
        'stats': data.get('stats', {}),
        'hero_images_count': len(data.get('hero_images', [])),
        'gallery_images_count': len(data.get('gallery_images', [])),
    }
    (county_dir / 'info.json').write_text(json.dumps(info, indent=2, default=str))
    
    # ─── sectors.json ────────────────────────────────────────────
    sectors = data.get('sectors', [])
    (county_dir / 'sectors.json').write_text(json.dumps(sectors, indent=2))
    
    # ─── executive.json ─────────────────────────────────────────
    execs = data.get('executive', [])
    (county_dir / 'executive.json').write_text(json.dumps(execs, indent=2))
    
    # ─── Images ─────────────────────────────────────────────────
    img_count = 0
    # Copy hero images
    for img in data.get('hero_images', []):
        url = img.get('url', '')
        if not url:
            continue
        fname = re.sub(r'[^a-zA-Z0-9._-]', '_', url.split('/')[-1])
        if not fname or len(fname) > 60:
            fname = hashlib.md5(url.encode()).hexdigest()[:16] + '.jpg'
        dest = hero_dir / fname
        # Try from local cache first then download
        cached = SCRAPED_IMAGES / dir_name / f"hero_{fname}"
        if cached.exists():
            shutil.copy2(cached, dest)
            img_count += 1
        else:
            # Try downloading
            try:
                req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
                with urllib.request.urlopen(req, timeout=10) as r:
                    data_bytes = r.read()
                    if len(data_bytes) > 1024 and 'image' in r.headers.get('Content-Type', ''):
                        dest.write_bytes(data_bytes)
                        img_count += 1
            except:
                pass
    
    # Copy gallery images
    for img in data.get('gallery_images', []):
        url = img.get('url', '')
        if not url:
            continue
        fname = re.sub(r'[^a-zA-Z0-9._-]', '_', url.split('/')[-1])
        if not fname or len(fname) > 60:
            fname = hashlib.md5(url.encode()).hexdigest()[:16] + '.jpg'
        dest = gallery_dir / fname
        cached = SCRAPED_IMAGES / dir_name / f"gallery_{fname}"
        if cached.exists():
            shutil.copy2(cached, dest)
            img_count += 1
    
    total_imgs += img_count
    print(f"  {name:25s} ✓ {len(sectors):3d} sectors, {img_count:3d} images, {len(execs):2d} leaders")

print(f"\n{'='*50}")
print(f"✅ Organized {len([d for d in OUTPUT.iterdir() if d.is_dir()])} counties")
print(f"   Total images: {total_imgs}")
print(f"   Location: {OUTPUT}")

# Save master index
index = []
for d in sorted(OUTPUT.iterdir()):
    if d.is_dir():
        info_file = d / 'info.json'
        if info_file.exists():
            info = json.loads(info_file.read_text())
            index.append({'name': info['name'], 'slug': info['slug'], 'status': info['status']})

(OUTPUT / 'index.json').write_text(json.dumps(index, indent=2))
print(f"\nMaster index: {OUTPUT / 'index.json'}")
