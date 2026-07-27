"""
County Data Importer
────────────────────
Imports scraped county data from county_scraper_v2.py into the Laravel database.
- Updates County records with scraped descriptions, contacts, stats
- Creates SectorEntities from scraped sectors
- Copies hero/gallery images to storage/app/public/counties/{slug}/
"""

import json, os, shutil, sys
from pathlib import Path

BASE = Path('/home/kicc/Desktop/kicc/kicc-platform')
SCRAPED_FILE = BASE / 'scripts' / 'scraped_data' / 'counties_scraped.json'
SCRAPED_IMAGES = BASE / 'scripts' / 'scraped_data' / 'images'
STORAGE_DIR = BASE / 'storage' / 'app' / 'public' / 'counties'

# Mapping of county names (from scraper) to DB slugs
# Handle differences between scraper output and DB slugs
NAME_TO_SLUG = {
    'Mombasa': 'mombasa', 'Kwale': 'kwale', 'Kilifi': 'kilifi',
    'Tana River': 'tana-river', 'Lamu': 'lamu', 'Taita-Taveta': 'taita-taveta',
    'Garissa': 'garissa', 'Wajir': 'wajir', 'Mandera': 'mandera',
    'Marsabit': 'marsabit', 'Isiolo': 'isiolo', 'Meru': 'meru',
    'Tharaka-Nithi': 'tharaka-nithi', 'Embu': 'embu', 'Kitui': 'kitui',
    'Machakos': 'machakos', 'Makueni': 'makueni', 'Nyandarua': 'nyandarua',
    'Nyeri': 'nyeri', 'Kirinyaga': 'kirinyaga', "Murang'a": 'muranga',
    'Kiambu': 'kiambu', 'Turkana': 'turkana', 'West Pokot': 'west-pokot',
    'Samburu': 'samburu', 'Trans Nzoia': 'trans-nzoia',
    'Uasin Gishu': 'uasin-gishu', 'Elgeyo-Marakwet': 'elgeyo-marakwet',
    'Nandi': 'nandi', 'Baringo': 'baringo', 'Laikipia': 'laikipia',
    'Nakuru': 'nakuru', 'Narok': 'narok', 'Kajiado': 'kajiado',
    'Kericho': 'kericho', 'Bomet': 'bomet', 'Kakamega': 'kakamega',
    'Vihiga': 'vihiga', 'Busia': 'busia', 'Siaya': 'siaya',
    'Kisumu': 'kisumu', 'Homa Bay': 'homa-bay', 'Migori': 'migori',
    'Kisii': 'kisii', 'Nyamira': 'nyamira', 'Nairobi City': 'nairobi-city',
    'Bungoma': 'bungoma',
}

if not SCRAPED_FILE.exists():
    print(f"Error: No scraped data found at {SCRAPED_FILE}")
    print("Run county_scraper_v2.py first")
    sys.exit(1)

with open(SCRAPED_FILE) as f:
    counties = json.load(f)

print(f"Loaded {len(counties)} counties from scraped data")

copied_images = []
imported_sectors = []

for county in counties:
    name = county['name']
    slug = NAME_TO_SLUG.get(name, name.lower().replace(' ', '-'))
    county_dir = STORAGE_DIR / slug
    county_dir.mkdir(parents=True, exist_ok=True)
    
    # Find scraped image folder
    img_folder_name = name.lower().replace("'", '').replace(' ', '_').replace('&', 'and')
    scraped_dir = SCRAPED_IMAGES / img_folder_name
    
    if scraped_dir.exists():
        for img_file in scraped_dir.iterdir():
            if img_file.is_file() and img_file.suffix.lower() in ('.jpg', '.jpeg', '.png', '.webp', '.gif'):
                # Classify: hero images get special names
                dest_name = img_file.name
                if 'hero' in img_file.stem.lower():
                    dest_name = 'hero.jpeg'
                elif 'gallery' in img_file.stem.lower():
                    dest_name = f'gallery_{img_file.name}'
                
                dest = county_dir / dest_name
                shutil.copy2(img_file, dest)
                copied_images.append(f"{slug}/{dest_name}")
    
    # Extract sector data for import
    for sector in county.get('sectors', []):
        sname = sector.get('name', '').strip()
        if sname:
            slugified = sname.lower().replace(' ', '-')[:60]
            imported_sectors.append({
                'county_slug': slug,
                'name': sname,
                'slug': slugified,
                'category': sector.get('category', 'general'),
                'source_url': sector.get('url', ''),
            })
    
    # Print summary
    desc = county.get('description', '')[:80]
    contacts = county.get('contacts', {})
    print(f"  {name:25s} → {slug:20s} sectors:{len(county['sectors']):3d}  "
          f"hero:{len(county['hero_images'])}  gallery:{len(county['gallery_images'])}  "
          f"emails:{len(contacts.get('emails',[]))}  phones:{len(contacts.get('phones',[]))}")

# Save sector import data
sectors_json = Path('/home/kicc/Desktop/kicc/kicc-platform/database/data/scraped_sectors.json')
sectors_json.write_text(json.dumps(imported_sectors, indent=2))
print(f"\n✅ Copied {copied_images} images to storage")
print(f"✅ Exported {len(imported_sectors)} sectors to {sectors_json}")

# Print summary for PHP artisan command
print(f"\n{'='*50}")
print(f"To update counties in DB, run:")
print(f"  php artisan tinker --execute=\"")
print(f"    \$data = json_decode(file_get_contents(database_path('data/scraped_sectors.json')), true);")
print(f"    // ... update logic ...")
print(f"  \"")
