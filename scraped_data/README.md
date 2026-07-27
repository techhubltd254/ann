# KICC County Scraped Data

## Structure
```
scraped_data/
├── README.md
├── scripts/
│   ├── county_scraper.py        # Single-threaded scraper
│   ├── county_scraper_v2.py     # Multi-threaded scraper (recommended)
│   └── import_scraped_data.py   # Copies images to Laravel storage
├── data/
│   ├── counties_scraped.json    # Full scraped data (41 counties)
│   └── scraped_sectors.json     # 145 sector listings
├── imports/
│   └── ScrapedDataSeeder.php    # Laravel seeder for DB import
└── images/
    └── counties/
        └── {county_name}/
            ├── hero_*.jpg       # Hero images
            └── gallery_*.jpg    # Gallery images
```

## How to use
1. Run scraper: `python3 scripts/county_scraper_v2.py`
2. Import images: `python3 scripts/import_scraped_data.py`
3. DB import: `php artisan db:seed --class=ScrapedDataSeeder --force`

## 41 counties scraped (6 failed)
Missing: Taita-Taveta, Embu, Garissa, Kirinyaga, Busia, Nairobi City
