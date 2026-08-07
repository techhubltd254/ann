"""
KICC County Website Scraper
────────────────────────────
Scrapes all 47 Kenyan county .go.ke websites for:
  - Hero/cover images
  - Sector/department listings
  - Executive/leadership profiles
  - Contact information
  - Events/notices
  - Key statistical data
  - Downloads/documents

Usage:  python3 county_scraper.py [--resume] [--single county_slug]
Output: /home/kicc/Desktop/kicc/kicc-platform/scripts/scraped_data/
"""

import json, os, re, sys, time, hashlib, urllib.request, urllib.error
from pathlib import Path
from datetime import datetime
from collections import defaultdict

# ─── CONFIG ────────────────────────────────────────────────────────────────────
BASE_DIR = Path(__file__).parent / 'scraped_data'
OUTPUT_JSON = BASE_DIR / 'counties_scraped.json'
IMAGES_DIR = BASE_DIR / 'images'
HEADERS = {'User-Agent': 'Mozilla/5.0 (compatible; KICCBot/1.0; +https://kicctest.org)'}
REQUEST_TIMEOUT = 20
DELAY_BETWEEN_REQUESTS = 1.5  # be polite

# ─── COUNTY DOMAIN MAPPING ─────────────────────────────────────────────────────
# Based on official Kenyan county government websites
COUNTY_DOMAINS = {
    'Mombasa':        'https://mombasa.go.ke',
    'Kwale':          'https://kwale.go.ke',
    'Kilifi':         'https://kilifi.go.ke',
    'Tana River':     'https://tana-river.go.ke',
    'Lamu':           'https://lamu.go.ke',
    'Taita-Taveta':   'https://taitataveta.go.ke',
    'Garissa':        'https://garissa.go.ke',
    'Wajir':          'https://wajir.go.ke',
    'Mandera':        'https://mandera.go.ke',
    'Marsabit':       'https://marsabit.go.ke',
    'Isiolo':         'https://isiolo.go.ke',
    'Meru':           'https://meru.go.ke',
    'Tharaka-Nithi':  'https://tharakanithi.go.ke',
    'Embu':           'https://embu.go.ke',
    'Kitui':          'https://kitui.go.ke',
    'Machakos':       'https://machakos.go.ke',
    'Makueni':        'https://makueni.go.ke',
    'Nyandarua':      'https://nyandarua.go.ke',
    'Nyeri':          'https://nyeri.go.ke',
    'Kirinyaga':      'https://kirinyaga.go.ke',
    "Murang'a":       'https://muranga.go.ke',
    'Kiambu':         'https://kiambu.go.ke',
    'Turkana':        'https://turkana.go.ke',
    'West Pokot':     'https://westpokot.go.ke',
    'Samburu':        'https://samburu.go.ke',
    'Trans Nzoia':    'https://transnzoia.go.ke',
    'Uasin Gishu':    'https://uasingishu.go.ke',
    'Elgeyo-Marakwet':'https://elgeyomarakwet.go.ke',
    'Nandi':          'https://nandi.go.ke',
    'Baringo':        'https://baringo.go.ke',
    'Laikipia':       'https://laikipia.go.ke',
    'Nakuru':         'https://nakuru.go.ke',
    'Narok':          'https://narok.go.ke',
    'Kajiado':        'https://kajiado.go.ke',
    'Kericho':        'https://kericho.go.ke',
    'Bomet':          'https://bomet.go.ke',
    'Kakamega':       'https://kakamega.go.ke',
    'Vihiga':         'https://vihiga.go.ke',
    'Busia':          'https://busia.go.ke',
    'Siaya':          'https://siaya.go.ke',
    'Kisumu':         'https://kisumu.go.ke',
    'Homa Bay':       'https://homabay.go.ke',
    'Migori':         'https://migori.go.ke',
    'Kisii':          'https://kisii.go.ke',
    'Nyamira':        'https://nyamira.go.ke',
    'Nairobi City':   'https://nairobi.go.ke',
    'Bungoma':        'https://bungoma.go.ke',
}

# ─── SECTOR KEYWORDS ───────────────────────────────────────────────────────────
SECTOR_KEYWORDS = [
    'agriculture', 'tourism', 'health', 'education', 'trade', 'energy',
    'environment', 'water', 'roads', 'transport', 'ict', 'gender',
    'social services', 'culture', 'youth', 'sports', 'land', 'planning',
    'finance', 'budget', 'cooperative', 'livestock', 'fisheries',
    'forestry', 'disaster', 'security', 'legal', 'procurement',
    'communication', 'public service', 'administration',
]


def fetch(url, timeout=REQUEST_TIMEOUT):
    """Fetch a URL with retry logic."""
    for attempt in range(3):
        try:
            req = urllib.request.Request(url, headers=HEADERS)
            with urllib.request.urlopen(req, timeout=timeout) as resp:
                html = resp.read().decode('utf-8', errors='replace')
                return html, resp.geturl(), dict(resp.headers)
        except Exception as e:
            if attempt == 2:
                return None, None, None
            time.sleep(2 ** attempt)


def extract_meta(html, url):
    """Extract meta tags, title, description."""
    result = {'url': url, 'title': '', 'description': '', 'keywords': []}
    m = re.search(r'<title>([^<]+)</title>', html, re.I)
    if m: result['title'] = m.group(1).strip()
    
    for meta_type, attr in [('description', 'name'), ('keywords', 'name'), ('og:description', 'property'), ('og:image', 'property')]:
        pattern = rf'<meta\s+[^>]*{re.escape(attr)}=["\']{re.escape(meta_type)}["\'][^>]*content=["\']([^"\']+)["\']'
        m = re.search(pattern, html, re.I)
        if not m:
            pattern = rf'<meta\s+[^>]*content=["\']([^"\']+)["\'][^>]*{re.escape(attr)}=["\']{re.escape(meta_type)}["\']'
            m = re.search(pattern, html, re.I)
        if m:
            if meta_type == 'keywords':
                result['keywords'] = [k.strip() for k in m.group(1).split(',')]
            elif meta_type == 'og:image':
                result['og_image'] = m.group(1)
            else:
                result[meta_type] = m.group(1)[:500]
    
    return result


def extract_sectors(html, base_url):
    """Extract sector/department listings from menus and content."""
    sectors = []
    seen = set()
    
    # Extract from navigation menus
    nav_patterns = [
        r'<nav[^>]*>.*?</nav>',
        r'<ul[^>]*class=["\'][^"\']*(?:menu|nav|dropdown)[^"\']*["\'][^>]*>.*?</ul>',
        r'<div[^>]*class=["\'][^"\']*(?:menu|nav|dropdown)[^"\']*["\'][^>]*>.*?</div>',
    ]
    
    for pattern in nav_patterns:
        for m in re.finditer(pattern, html, re.I | re.S):
            nav_html = m.group()
            links = re.findall(r'<a[^>]*href=["\']([^"\']+)["\'][^>]*>([^<]+)</a>', nav_html, re.I)
            for href, text in links:
                text = re.sub(r'<[^>]+>', '', text).strip()
                if len(text) > 2 and text.lower() not in seen:
                    for kw in SECTOR_KEYWORDS:
                        if kw in text.lower():
                            full_url = urllib.parse.urljoin(base_url, href) if href else ''
                            sectors.append({
                                'name': text,
                                'url': full_url,
                                'keyword_match': kw,
                            })
                            seen.add(text.lower())
                            break
    
    # Also look for section headings that match sectors
    heading_pattern = r'<(h[2-4])[^>]*>([^<]+)</\1>'
    for m in re.finditer(heading_pattern, html, re.I):
        text = m.group(2).strip()
        if len(text) > 2 and text.lower() not in seen:
            for kw in SECTOR_KEYWORDS:
                if kw in text.lower():
                    sectors.append({'name': text, 'url': '', 'keyword_match': kw})
                    seen.add(text.lower())
                    break
    
    return sectors


def extract_images(html, base_url):
    """Extract image URLs from the page."""
    images = []
    seen_urls = set()
    skip_patterns = ['logo', 'icon', 'banner', 'wp-content/plugins', '1x1', 'pixel', 'tracking']
    
    for m in re.finditer(r'<img[^>]*src=["\']([^"\']+)["\']', html, re.I):
        src = m.group(1)
        if any(s in src.lower() for s in skip_patterns):
            continue
        full_url = urllib.parse.urljoin(base_url, src)
        if full_url in seen_urls:
            continue
        seen_urls.add(full_url)
        
        alt = ''
        am = re.search(r'alt=["\']([^"\']*)["\']', m.group())
        if am: alt = am.group(1)
        
        images.append({'url': full_url, 'alt': alt[:200], 'source': base_url})
    
    return images


def extract_contacts(html):
    """Extract contact information."""
    contacts = {'emails': set(), 'phones': set(), 'physical_address': '', 'postal': ''}
    
    for m in re.finditer(r'[\w.+-]+@[\w-]+\.[\w.-]+', html):
        contacts['emails'].add(m.group())
    
    # Kenyan phone patterns: +254, 0xxx, 07xx
    for m in re.finditer(r'(?:\+254|0)[17]\d{1,2}[\s-]?\d{3}[\s-]?\d{3}(?:\s*/\s*(?:\+254|0)[17]\d{1,2}[\s-]?\d{3}[\s-]?\d{3})?', html):
        contacts['phones'].add(m.group().strip())
    
    # Look for physical address in contact sections
    contact_section = re.search(r'(?:physical\s*address|location|office\s*address)[^<]*?<[^>]*>([^<]{10,200})', html, re.I | re.S)
    if contact_section:
        contacts['physical_address'] = contact_section.group(1).strip()
    
    postal = re.search(r'(?:P\.?\s*O\.?\s*Box|postal)[^<]*?<[^>]*>([^<]{10,100})', html, re.I)
    if postal:
        contacts['postal'] = postal.group(1).strip()
    
    return {k: list(v) if isinstance(v, set) else v for k, v in contacts.items()}


def extract_executive(html):
    """Extract executive/leadership profiles."""
    leaders = []
    
    # Look for common patterns like "H.E. Governor", "Deputy Governor", "County Secretary"
    patterns = [
        (r'(?:H\.?\s*E\.?\s*|His\s+Excellency|Her\s+Excellency|Hon\.?)\s+([^<]{10,80}?(?:Governor|Deputy\s+Governor|Speaker|County\s+Secretary))', re.I),
        (r'<h[2-4][^>]*>(?:Our\s+)?(?:Leadership|Executive|Management)</h[2-4]>([^<]{100,500})', re.I | re.S),
    ]
    
    for pattern, flags in patterns:
        for m in re.finditer(pattern, html, flags):
            text = re.sub(r'<[^>]+>', '', m.group()).strip()
            if text and len(text) < 200:
                leaders.append(text[:200])
    
    return leaders


def extract_pages(html, base_url):
    """Find important internal pages."""
    important = defaultdict(list)
    page_patterns = [
        ('about', r'about|who-we-are|profile|history|overview'),
        ('departments', r'departments|sectors|directorates|ministries'),
        ('services', r'services|citizen|how-to|applications'),
        ('projects', r'projects|initiatives|flagship|development'),
        ('tenders', r'tenders|procurement|bids|suppliers'),
        ('news', r'news|events|notices|announcements|press'),
        ('contact', r'contact|reach-us|feedback'),
        ('downloads', r'downloads|documents|reports|publications'),
    ]
    
    for link_type, pattern in page_patterns:
        for m in re.finditer(r'<a[^>]*href=["\']([^"\']+)["\']([^>]*)>', html, re.I):
            href = m.group(1)
            if re.search(pattern, href, re.I) and not href.startswith('#') and not href.startswith('javascript'):
                full_url = urllib.parse.urljoin(base_url, href)
                important[link_type].append(full_url)
    
    # Deduplicate
    return {k: list(dict.fromkeys(v))[:5] for k, v in important.items()}


def download_image(url, dest_path):
    """Download an image to the destination path."""
    try:
        req = urllib.request.Request(url, headers=HEADERS)
        with urllib.request.urlopen(req, timeout=15) as resp:
            data = resp.read()
            if len(data) < 1024:  # skip tiny files
                return False
            content_type = resp.headers.get('Content-Type', '')
            if 'image' not in content_type:
                return False
            ext = content_type.split('/')[-1]
            if ext not in ('jpeg', 'jpg', 'png', 'webp', 'gif'):
                ext = 'jpg'
            dest_path.parent.mkdir(parents=True, exist_ok=True)
            dest_path.write_bytes(data)
            return True
    except Exception:
        return False


def scrape_county(name, url, resume_data=None):
    """Scrape a single county website."""
    print(f"  Fetching {url} ...", end=' ', flush=True)
    html, final_url, headers = fetch(url)
    
    if not html:
        print("FAILED")
        return None
    
    print("OK")
    
    result = {
        'name': name,
        'url': url,
        'final_url': final_url,
        'scraped_at': datetime.now().isoformat(),
        'meta': extract_meta(html, url),
        'sectors': extract_sectors(html, url),
        'hero_images': [],
        'gallery_images': [],
        'contacts': extract_contacts(html),
        'executive': extract_executive(html),
        'pages': extract_pages(html, url),
        'stats': {},
    }
    
    # Extract images (prioritize hero/carousel images)
    all_images = extract_images(html, url)
    result['gallery_images'] = all_images[:20]
    
    # Try to find hero images from common patterns
    hero_patterns = [
        r'background-image\s*:\s*url\(["\']?([^"\')\s]+)["\']?\)',
        r'<div[^>]*class=["\'][^"\']*hero[^"\']*["\'][^>]*style=["\'][^"\']*background[^:]*:\s*url\(["\']?([^"\')\s]+)',
        r'<img[^>]*class=["\'][^"\']*(?:hero|banner|slide|cover)[^"\']*["\'][^>]*src=["\']([^"\']+)',
    ]
    seen_hero = set()
    for pattern in hero_patterns:
        for m in re.finditer(pattern, html, re.I):
            img_url = urllib.parse.urljoin(url, m.group(1))
            if img_url not in seen_hero:
                seen_hero.add(img_url)
                result['hero_images'].append({'url': img_url, 'type': 'hero'})
    
    # Extract statistical data from counters, charts, etc.
    stat_patterns = [
        (r'(\d[\d,]*)\s*(?:\+|M|k)?\s*(?:Years|Counties|Sectors|Projects|Staff|Citizens|Residents)', 'count'),
        (r'class=["\'][^"\']*(?:counter|stat|number|count)[^"\']*["\'][^>]*>(\d[\d,.]*)', 'counter'),
    ]
    for pat, stat_type in stat_patterns:
        for m in re.finditer(pat, html, re.I):
            val = m.group(1).replace(',', '')
            if val.isdigit():
                key = f'stat_{stat_type}_{len(result["stats"])}'
                result['stats'][key] = int(val)
    
    # Download hero images
    img_dir = IMAGES_DIR / name.lower().replace("'", '').replace(' ', '_').replace('&', 'and')
    downloaded = []
    for img_info in result['hero_images'][:3]:
        fname = re.sub(r'[^a-zA-Z0-9._-]', '_', img_info['url'].split('/')[-1])
        dest = img_dir / f'hero_{fname}'
        if download_image(img_info['url'], dest):
            downloaded.append(str(dest.relative_to(BASE_DIR)))
            img_info['local_path'] = str(dest.relative_to(BASE_DIR))
    
    # Download first few gallery images
    for img_info in result['gallery_images'][:5]:
        fname = re.sub(r'[^a-zA-Z0-9._-]', '_', img_info['url'].split('/')[-1])
        if not fname:
            fname = hashlib.md5(img_info['url'].encode()).hexdigest()[:16] + '.jpg'
        dest = img_dir / f'gallery_{fname}'
        if download_image(img_info['url'], dest):
            img_info['local_path'] = str(dest.relative_to(BASE_DIR))
    
    # Explore key internal pages
    for page_type, urls in result['pages'].items():
        if urls:
            sub_html, _, _ = fetch(urls[0])
            if sub_html:
                sub_sectors = extract_sectors(sub_html, urls[0])
                for s in sub_sectors:
                    if s not in result['sectors']:
                        result['sectors'].append(s)
                sub_images = extract_images(sub_html, urls[0])
                result['gallery_images'].extend(sub_images[:10])
                sub_contacts = extract_contacts(sub_html)
                for k in ['emails', 'phones']:
                    if isinstance(sub_contacts.get(k), list):
                        if not isinstance(result['contacts'].get(k), list):
                            result['contacts'][k] = []
                        result['contacts'][k].extend(sub_contacts[k])
    
    # Deduplicate
    result['sectors'] = list({s['name']: s for s in result['sectors']}.values())
    result['gallery_images'] = list({i['url']: i for i in result['gallery_images']}.values())
    for k in ['emails', 'phones']:
        if isinstance(result['contacts'].get(k), list):
            result['contacts'][k] = list(dict.fromkeys(result['contacts'][k]))
    
    return result


def main():
    BASE_DIR.mkdir(parents=True, exist_ok=True)
    IMAGES_DIR.mkdir(parents=True, exist_ok=True)
    
    # Load existing data if resuming
    resume_data = {}
    if OUTPUT_JSON.exists():
        with open(OUTPUT_JSON) as f:
            resume_data = {c['name']: c for c in json.load(f)}
    
    single = None
    resume = False
    for arg in sys.argv[1:]:
        if arg.startswith('--single='):
            single = arg.split('=', 1)[1]
        elif arg == '--resume':
            resume = True
    
    results = []
    total = len(COUNTY_DOMAINS)
    
    for i, (name, url) in enumerate(COUNTY_DOMAINS.items(), 1):
        if single and name.lower() != single.lower() and name.lower().replace(' ', '_') != single.lower():
            continue
        
        if resume and name in resume_data:
            print(f"[{i}/{total}] {name} — skipped (already scraped)")
            results.append(resume_data[name])
            continue
        
        print(f"\n[{i}/{total}] {name}")
        data = scrape_county(name, url, resume_data.get(name))
        if data:
            results.append(data)
        else:
            # Append existing if scraping failed
            if name in resume_data:
                results.append(resume_data[name])
        
        time.sleep(DELAY_BETWEEN_REQUESTS)
    
    # Merge with existing data
    existing = {}
    if OUTPUT_JSON.exists():
        with open(OUTPUT_JSON) as f:
            existing = {c['name']: c for c in json.load(f)}
    
    for r in results:
        existing[r['name']] = r
    
    # Save
    OUTPUT_JSON.write_text(json.dumps(list(existing.values()), indent=2, default=str))
    
    print(f"\n{'='*50}")
    print(f"Scraped {len(existing)} counties")
    print(f"Data saved to: {OUTPUT_JSON}")
    print(f"Images saved to: {IMAGES_DIR}")
    
    # Summary
    total_sectors = sum(len(c.get('sectors', [])) for c in existing.values())
    total_imgs = sum(len(c.get('gallery_images', [])) for c in existing.values())
    total_leaders = sum(len(c.get('executive', [])) for c in existing.values())
    print(f"Total sectors: {total_sectors}")
    print(f"Total images: {total_imgs}")
    print(f"Total leaders: {total_leaders}")


if __name__ == '__main__':
    # urllib.parse is needed in extract_pages
    import urllib.parse
    main()
