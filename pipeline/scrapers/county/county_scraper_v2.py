"""
KICC County Scraper v2 — Multi-threaded
───────────────────────────────────────
Scrapes 47 Kenyan county .go.ke websites in parallel batches.
Outputs structured JSON + downloads hero images.
"""

import json, os, re, sys, time, hashlib, urllib.request, urllib.error, urllib.parse
from pathlib import Path
from datetime import datetime
from collections import defaultdict
from concurrent.futures import ThreadPoolExecutor, as_completed

BASE_DIR = Path('/home/kicc/Desktop/kicc/kicc-one/data/scraped_counties/_raw')
OUTPUT_JSON = BASE_DIR / 'counties_scraped.json'
IMAGES_DIR = BASE_DIR / 'images'
HEADERS = {'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'}
REQUEST_TIMEOUT = 15
DELAY = 0.5

# All 47 counties with verified/alternative URLs
COUNTY_SITES = {
    'Mombasa':         ['https://mombasa.go.ke'],
    'Kwale':           ['https://kwale.go.ke'],
    'Kilifi':          ['https://kilifi.go.ke'],
    'Tana River':      ['https://tanariver.go.ke', 'https://tana-river.go.ke'],
    'Lamu':            ['https://lamu.go.ke'],
    'Taita-Taveta':    ['https://taitataveta.go.ke', 'https://www.taitataveta.go.ke', 'https://web.archive.org/web/2023id_/https://taitataveta.go.ke/'],
    'Garissa':         ['https://garissa.go.ke'],
    'Wajir':           ['https://wajir.go.ke'],
    'Mandera':         ['https://mandera.go.ke'],
    'Marsabit':        ['https://marsabit.go.ke'],
    'Isiolo':          ['https://isiolo.go.ke'],
    'Meru':            ['https://meru.go.ke'],
    'Tharaka-Nithi':   ['https://tharakanithi.go.ke'],
    'Embu':            ['https://embu.go.ke', 'http://embu.go.ke', 'https://web.archive.org/web/2024id_/https://embu.go.ke/'],
    'Kitui':           ['https://kitui.go.ke'],
    'Machakos':        ['https://machakos.go.ke'],
    'Makueni':         ['https://makueni.go.ke'],
    'Nyandarua':       ['https://nyandarua.go.ke'],
    'Nyeri':           ['https://nyeri.go.ke'],
    'Kirinyaga':       ['https://kirinyaga.go.ke', 'http://kirinyaga.go.ke', 'https://web.archive.org/web/2024id_/https://kirinyaga.go.ke/'],
    "Murang'a":        ['https://muranga.go.ke'],
    'Kiambu':          ['https://kiambu.go.ke'],
    'Turkana':         ['https://turkana.go.ke'],
    'West Pokot':      ['https://westpokot.go.ke'],
    'Samburu':         ['https://samburu.go.ke'],
    'Trans Nzoia':     ['https://transnzoia.go.ke'],
    'Uasin Gishu':     ['https://uasingishu.go.ke'],
    'Elgeyo-Marakwet': ['https://elgeyomarakwet.go.ke'],
    'Nandi':           ['https://nandi.go.ke'],
    'Baringo':         ['https://baringo.go.ke'],
    'Laikipia':        ['https://laikipia.go.ke'],
    'Nakuru':          ['https://nakuru.go.ke'],
    'Narok':           ['https://narok.go.ke'],
    'Kajiado':         ['https://kajiado.go.ke'],
    'Kericho':         ['https://kericho.go.ke'],
    'Bomet':           ['https://bomet.go.ke'],
    'Kakamega':        ['https://kakamega.go.ke'],
    'Vihiga':          ['https://vihiga.go.ke'],
    'Busia':           ['https://busia.go.ke', 'https://busiacounty.go.ke'],
    'Siaya':           ['https://siaya.go.ke'],
    'Kisumu':          ['https://kisumu.go.ke'],
    'Homa Bay':        ['https://homabay.go.ke'],
    'Migori':          ['https://migori.go.ke'],
    'Kisii':           ['https://kisii.go.ke'],
    'Nyamira':         ['https://nyamira.go.ke'],
    'Nairobi City':    ['https://nairobi.go.ke', 'https://county-nairobi.go.ke', 'https://web.archive.org/web/2022id_/https://nairobi.go.ke/'],
    'Bungoma':         ['https://bungoma.go.ke'],
}

SECTOR_KW = [
    'agriculture','tourism','health','education','trade','energy',
    'environment','water','roads','transport','ict','gender',
    'social','culture','youth','sports','land','planning',
    'finance','cooperative','livestock','fisheries','forestry',
    'security','legal','procurement','communication','public service',
]


def fetch_urls(urls, timeout=REQUEST_TIMEOUT):
    """Try multiple URLs, return first successful response."""
    for url in urls:
        try:
            req = urllib.request.Request(url, headers=HEADERS)
            with urllib.request.urlopen(req, timeout=timeout) as r:
                return r.read().decode('utf-8', errors='replace'), r.geturl()
        except Exception:
            continue
    return None, None


def extract_data(name, html, base_url):
    """Extract all useful data from HTML."""
    result = {
        'name': name, 'source_url': base_url,
        'scraped_at': datetime.now().isoformat(),
        'title': '', 'description': '', 'sectors': [],
        'hero_images': [], 'gallery_images': [], 'contacts': {},
        'executive': [], 'pages': {}, 'stats': {},
    }

    # Title
    m = re.search(r'<title>([^<]+)</title>', html, re.I)
    if m: result['title'] = m.group(1).strip()
    
    # Meta description
    m = re.search(r'<meta\s+[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)', html, re.I)
    if m: result['description'] = m.group(1)[:500]
    
    # Images
    seen = set()
    for m in re.finditer(r'<img[^>]*src=["\']([^"\']+)["\']', html, re.I):
        src = m.group(1)
        if any(s in src.lower() for s in ['logo','icon','pixel','tracking','1x1']):
            continue
        full = urllib.parse.urljoin(base_url, src)
        if full not in seen:
            seen.add(full)
            alt = ''
            am = re.search(r'alt=["\']([^"\']*)["\']', m.group())
            if am: alt = am.group(1)
            # Classify: hero-type images vs regular
            classes = m.group()[:200].lower()
            is_hero = any(k in classes for k in ['hero','banner','slide','cover','background'])
            img_info = {'url': full, 'alt': alt[:200]}
            if is_hero:
                result['hero_images'].append(img_info)
            else:
                result['gallery_images'].append(img_info)
    
    # Hero backgrounds inline CSS
    for m in re.finditer(r'background[^:]*:\s*[^;]*url\(["\']?([^"\')\s]+)["\']?\)', html, re.I):
        full = urllib.parse.urljoin(base_url, m.group(1))
        if full not in seen:
            seen.add(full)
            result['hero_images'].append({'url': full, 'type': 'background'})

    # Sectors from navigation
    nav_match = re.search(r'<nav[^>]*>.*?</nav>', html, re.I | re.S)
    if nav_match:
        nav_html = nav_match.group()
        for m in re.finditer(r'<a[^>]*href=["\']([^"\']+)["\'][^>]*>([^<]+)</a>', nav_html, re.I):
            href, text = m.group(1), m.group(2).strip()
            text = re.sub(r'<[^>]+>', '', text).strip()
            for kw in SECTOR_KW:
                if kw in text.lower() and len(text) > 2:
                    full = urllib.parse.urljoin(base_url, href) if href else ''
                    result['sectors'].append({'name': text, 'url': full, 'category': kw})
                    break
    
    # Sectors from headings
    for m in re.finditer(r'<(h[2-4])[^>]*>([^<]+)</\1>', html, re.I):
        text = m.group(2).strip()
        for kw in SECTOR_KW:
            if kw in text.lower() and len(text) > 2:
                result['sectors'].append({'name': text, 'url': '', 'category': kw})
                break
    
    # Contacts
    emails = set(re.findall(r'[\w.+-]+@[\w-]+\.[\w.-]+', html))
    phones = set(re.findall(r'(?:\+254|0)[17]\d{1,2}[\s-]?\d{3}[\s-]?\d{3}', html))
    result['contacts']['emails'] = sorted(emails)[:10]
    result['contacts']['phones'] = sorted(phones)[:10]
    
    # Physical address
    addr = re.search(r'(?:physical\s*address|location|office\s*address)[^<]*?<[^>]*>([^<]{10,200})', html, re.I | re.S)
    if addr: result['contacts']['physical_address'] = addr.group(1).strip()
    
    # Executive
    for m in re.finditer(r'(?:H\.?\s*E\.?\s*|His\s+Excellency|Her\s+Excellency|Hon\.?)\s+([^<]{10,120}?(?:Governor|Deputy\s+Governor|Speaker|County\s+Secretary))', html, re.I):
        result['executive'].append(m.group(1).strip()[:200])
    for m in re.finditer(r'<h[2-4][^>]*>(?:Our\s+)?(?:Leadership|Executive|Management|County\s+Executive)</h[2-4]>([^<]{100,500})', html, re.I | re.S):
        result['executive'].append(re.sub(r'<[^>]+>', '', m.group()).strip()[:200])
    
    # Important pages
    page_types = {
        'about': r'about|who-we-are|profile|history',
        'departments': r'departments|sectors|directorates',
        'services': r'services|citizen|how-to|e-services',
        'projects': r'projects|initiatives|development',
        'tenders': r'tenders|procurement|bids',
        'news': r'news|events|notices|announcements',
        'contact': r'contact|reach-us|feedback',
        'downloads': r'downloads|documents|reports',
    }
    for ptype, pattern in page_types.items():
        found = []
        for m in re.finditer(r'<a[^>]*href=["\']([^"\']+)["\']', html, re.I):
            href = m.group(1)
            if re.search(pattern, href, re.I) and not href.startswith('#') and not href.startswith('javascript'):
                full = urllib.parse.urljoin(base_url, href)
                found.append(full)
        result['pages'][ptype] = list(dict.fromkeys(found))[:3]
    
    # Stats from counters
    for m in re.finditer(r'class=["\'][^"\']*(?:counter|stat|number|count)[^"\']*["\'][^>]*>(\d[\d,.]*)', html, re.I):
        val = m.group(1)
        if val.replace(',', '').replace('.', '').isdigit():
            result['stats'][f'counter_{len(result["stats"])}'] = val
    
    # Deduplicate sectors
    result['sectors'] = list({s['name']: s for s in result['sectors']}.values())
    # Limit images
    result['hero_images'] = result['hero_images'][:5]
    result['gallery_images'] = result['gallery_images'][:30]
    
    return result


def download_image(url, dest_path):
    """Download image file."""
    try:
        req = urllib.request.Request(url, headers=HEADERS)
        with urllib.request.urlopen(req, timeout=15) as r:
            data = r.read()
            ct = r.headers.get('Content-Type', '')
            if 'image' not in ct or len(data) < 1024:
                return False
            ext = ct.split('/')[-1]
            if ext not in ('jpeg', 'jpg', 'png', 'webp', 'gif'): ext = 'jpg'
            dest_path.parent.mkdir(parents=True, exist_ok=True)
            dest_path.write_bytes(data)
            return True
    except Exception:
        return False


def scrape_county(name, urls):
    """Scrape one county with fallback URLs."""
    html, final_url = fetch_urls(urls)
    if not html:
        return None
    
    data = extract_data(name, html, final_url)
    if not data:
        return None
    
    # Download hero images
    img_dir = IMAGES_DIR / name.lower().replace("'", '').replace(' ', '_').replace('&', 'and')
    for img in data['hero_images'][:3]:
        fname = re.sub(r'[^a-zA-Z0-9._-]', '_', img['url'].split('/')[-1])
        if not fname or len(fname) > 80:
            fname = hashlib.md5(img['url'].encode()).hexdigest()[:16] + '.jpg'
        dest = img_dir / f'hero_{fname}'
        if download_image(img['url'], dest):
            img['local'] = str(dest.relative_to(BASE_DIR))
    
    # Download first few gallery
    for img in data['gallery_images'][:8]:
        fname = re.sub(r'[^a-zA-Z0-9._-]', '_', img['url'].split('/')[-1])
        if not fname or len(fname) > 80:
            fname = hashlib.md5(img['url'].encode()).hexdigest()[:16] + '.jpg'
        dest = img_dir / f'gallery_{fname}'
        if download_image(img['url'], dest):
            img['local'] = str(dest.relative_to(BASE_DIR))
    
    return data


def main():
    BASE_DIR.mkdir(parents=True, exist_ok=True)
    IMAGES_DIR.mkdir(parents=True, exist_ok=True)
    
    # Load existing
    results = {}
    if OUTPUT_JSON.exists():
        with open(OUTPUT_JSON) as f:
            for c in json.load(f):
                results[c['name']] = c
    
    single = None
    for arg in sys.argv[1:]:
        if arg.startswith('--single='):
            single = arg.split('=', 1)[1]
    
    total = len(COUNTY_SITES)
    names_to_scrape = [n for n in COUNTY_SITES if not single or n.lower() == single.lower() or n.lower().replace(' ', '_') == single.lower()]
    
    print(f"Scraping {len(names_to_scrape)} counties ({total} total)...")
    
    def worker(name):
        if name in results:
            return name, results[name]
        data = scrape_county(name, COUNTY_SITES[name])
        return name, data
    
    done = 0
    with ThreadPoolExecutor(max_workers=6) as ex:
        futures = {ex.submit(worker, n): n for n in names_to_scrape}
        for f in as_completed(futures):
            name = futures[f]
            done += 1
            try:
                _, data = f.result()
                if data:
                    results[name] = data
                    sectors = len(data.get('sectors', []))
                    imgs = len(data.get('hero_images', [])) + len(data.get('gallery_images', []))
                    print(f"  [{done}/{total}] {name:25s} ✓ {sectors} sectors, {imgs} images")
                else:
                    print(f"  [{done}/{total}] {name:25s} ✗ FAILED")
            except Exception as e:
                print(f"  [{done}/{total}] {name:25s} ✗ ERROR: {str(e)[:50]}")
    
    # Save
    OUTPUT_JSON.write_text(json.dumps(list(results.values()), indent=2, default=str))
    
    total_s = sum(len(c.get('sectors', [])) for c in results.values())
    total_i = sum(len(c.get('hero_images', [])) + len(c.get('gallery_images', [])) for c in results.values())
    total_leaders = sum(len(c.get('executive', [])) for c in results.values())
    
    print(f"\n{'='*50}")
    print(f"Completed: {len(results)}/{total} counties")
    print(f"Total sectors: {total_s}")
    print(f"Total images: {total_i}")
    print(f"Total leaders: {total_leaders}")
    print(f"Data: {OUTPUT_JSON}")
    print(f"Images: {IMAGES_DIR}")


if __name__ == '__main__':
    main()
