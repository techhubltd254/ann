"""
Deep County Department/Sector Scraper v3
─────────────────────────────────────────
Aggressively extracts all departments, ministries, directorates, and sectors
from each county website by:
  1. Crawling the homepage nav menus
  2. Trying common department page URLs (/departments, /ministries, /directorates)
  3. Parsing footer links, sidebars, sitemaps
  4. Following department links to get sub-sectors
"""

import json, re, sys, time, hashlib, urllib.request, urllib.parse, urllib.error
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime

BASE = Path('/home/kicc/Desktop/kicc/kicc-platform')
OUTPUT = BASE / 'scraped_data'
HEADERS = {'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'}
TIMEOUT = 15

# Department-related keywords for matching
DEPT_KW = [
    'department', 'ministry', 'directorate', 'division', 'section', 'unit',
    'sector', 'authority', 'board', 'commission', 'council', 'agency',
    'office', 'secretariat', 'administration', 'director', 'commissioner',
]

SECTOR_NAMES_KW = [
    'agriculture', 'livestock', 'fisheries', 'irrigation',
    'health', 'medical', 'sanitation', 'hospital',
    'education', 'school', 'training', 'vocational',
    'tourism', 'wildlife', 'hospitality', 'culture',
    'trade', 'industry', 'commerce', 'investment', 'cooperative',
    'energy', 'petroleum', 'mining',
    'environment', 'forestry', 'climate', 'natural resource',
    'water', 'sewerage',
    'roads', 'transport', 'infrastructure', 'public work',
    'housing', 'urban development', 'planning', 'lands',
    'ict', 'information', 'communication', 'digital', 'e-government',
    'gender', 'social service', 'children', 'community',
    'youth', 'sports', 'recreation',
    'finance', 'budget', 'economic', 'planning', 'revenue',
    'procurement', 'supply chain',
    'legal', 'justice', 'law', 'compliance',
    'security', 'police', 'disaster', 'emergency',
    'public service', 'human resource', 'administration',
    'internal audit', 'risk', 'governance',
    'communication', 'public relation', 'media',
    'engineering', 'technical service',
    'library', 'museum', 'heritage',
    'corporate service', 'strategy',
    'monitoring', 'evaluation', 'quality',
    'partnership', 'donor', 'ngo',
]

COUNTY_SITES = {
    'Mombasa':         ['https://mombasa.go.ke'],
    'Kwale':           ['https://kwale.go.ke'],
    'Kilifi':          ['https://kilifi.go.ke'],
    'Tana River':      ['https://tana-river.go.ke', 'https://tanariver.go.ke'],
    'Lamu':            ['https://lamu.go.ke'],
    'Taita-Taveta':    ['https://taitataveta.go.ke', 'https://www.taitataveta.go.ke'],
    'Garissa':         ['https://garissa.go.ke'],
    'Wajir':           ['https://wajir.go.ke'],
    'Mandera':         ['https://mandera.go.ke'],
    'Marsabit':        ['https://marsabit.go.ke'],
    'Isiolo':          ['https://isiolo.go.ke'],
    'Meru':            ['https://meru.go.ke'],
    'Tharaka-Nithi':   ['https://tharakanithi.go.ke'],
    'Embu':            ['https://embu.go.ke', 'http://embu.go.ke'],
    'Kitui':           ['https://kitui.go.ke'],
    'Machakos':        ['https://machakos.go.ke'],
    'Makueni':         ['https://makueni.go.ke'],
    'Nyandarua':       ['https://nyandarua.go.ke'],
    'Nyeri':           ['https://nyeri.go.ke'],
    'Kirinyaga':       ['https://kirinyaga.go.ke', 'http://kirinyaga.go.ke'],
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
    'Busia':           ['https://busia.go.ke'],
    'Siaya':           ['https://siaya.go.ke'],
    'Kisumu':          ['https://kisumu.go.ke'],
    'Homa Bay':        ['https://homabay.go.ke'],
    'Migori':          ['https://migori.go.ke'],
    'Kisii':           ['https://kisii.go.ke'],
    'Nyamira':         ['https://nyamira.go.ke'],
    'Nairobi City':    ['https://nairobi.go.ke'],
    'Bungoma':         ['https://bungoma.go.ke'],
}


def fetch(url):
    for attempt in range(2):
        try:
            req = urllib.request.Request(url, headers=HEADERS)
            with urllib.request.urlopen(req, timeout=TIMEOUT) as r:
                return r.read().decode('utf-8', errors='replace'), r.geturl()
        except Exception as e:
            if attempt == 1: return None, str(e)[:60]
            time.sleep(1)
    return None, None


def extract_all_links(html, base_url):
    """Extract all internal links from HTML."""
    links = set()
    for m in re.finditer(r'<a[^>]*href=["\']([^"\']+)["\']', html, re.I):
        href = m.group(1)
        if href.startswith('#') or href.startswith('javascript') or href.startswith('mailto') or href.startswith('tel'):
            continue
        full = urllib.parse.urljoin(base_url, href)
        # Only internal links
        base_domain = urllib.parse.urlparse(base_url).netloc
        link_domain = urllib.parse.urlparse(full).netloc
        if base_domain in link_domain:
            links.add(full)
    return sorted(links)


def extract_text_content(html):
    """Get clean text from HTML."""
    text = re.sub(r'<script[^>]*>.*?</script>', '', html, flags=re.I | re.S)
    text = re.sub(r'<style[^>]*>.*?</style>', '', text, flags=re.I | re.S)
    text = re.sub(r'<[^>]+>', ' ', text)
    text = re.sub(r'\s+', ' ', text)
    return text.strip()


def extract_departments_from_nav(html, base_url):
    """Extract department names from navigation menus."""
    found = set()
    
    # Get all nav content
    nav_parts = re.findall(r'<nav[^>]*>.*?</nav>', html, re.I | re.S)
    nav_parts += re.findall(r'<div[^>]*class=["\'][^"\']*(?:menu|nav|dropdown|sidebar)[^"\']*["\'][^>]*>.*?</div>', html, re.I | re.S)
    nav_parts += re.findall(r'<ul[^>]*class=["\'][^"\']*(?:menu|nav|dropdown|sidebar)[^"\']*["\'][^>]*>.*?</ul>', html, re.I | re.S)
    
    nav_html = ' '.join(nav_parts) if nav_parts else html
    
    # Extract all link text from nav
    for m in re.finditer(r'<a[^>]*>([^<]+)</a>', nav_html, re.I):
        text = m.group(1).strip()
        if len(text) > 2:
            for kw in DEPT_KW + SECTOR_NAMES_KW:
                if kw in text.lower():
                    found.add(text)
                    break
    
    # Also look for list items with department-sounding text
    for m in re.finditer(r'<li[^>]*>([^<]+)</li>', nav_html, re.I):
        text = re.sub(r'<[^>]+>', '', m.group(1)).strip()
        if len(text) > 3:
            for kw in DEPT_KW + SECTOR_NAMES_KW:
                if kw in text.lower():
                    found.add(text)
                    break
    
    return sorted(found)


def extract_departments_from_footer(html):
    """Extract departments from footer."""
    found = set()
    footer = re.search(r'<footer[^>]*>.*?</footer>', html, re.I | re.S)
    if not footer:
        footer = re.search(r'<div[^>]*class=["\'][^"\']*footer[^"\']*["\'][^>]*>.*?</div>', html, re.I | re.S)
    
    if footer:
        text = extract_text_content(footer.group())
        # Look for department lists
        for m in re.finditer(r'(?:departments|ministries|sectors|services)[:\s]*([^<]{50,500})', text, re.I):
            content = m.group(1)
            for line in re.split(r'[•·,;\n]', content):
                line = line.strip()
                if len(line) > 3:
                    for kw in SECTOR_NAMES_KW:
                        if kw in line.lower():
                            found.add(line[:100])
                            break
    return sorted(found)


def extract_departments_from_content(html):
    """Extract department/sector mentions from page content."""
    found = set()
    text = extract_text_content(html)
    
    # Look for headings with department names
    for m in re.finditer(r'<(h[2-4])[^>]*>([^<]{3,150})</\1>', html, re.I):
        heading = m.group(2).strip()
        for kw in DEPT_KW + SECTOR_NAMES_KW:
            if kw in heading.lower():
                found.add(heading)
                break
    
    # Look for strong/b tags with department names
    for m in re.finditer(r'<(strong|b)[^>]*>([^<]{5,100})</\1>', html, re.I):
        text_tag = m.group(2).strip()
        for kw in DEPT_KW + SECTOR_NAMES_KW:
            if kw in text_tag.lower():
                found.add(text_tag)
                break
    
    return sorted(found)


def crawl_department_pages(base_url, known_links):
    """Visit department-like pages and extract more sectors."""
    dept_pages = set()
    
    # Try to find department page URLs
    for link in known_links[:30]:
        link_lower = link.lower()
        for kw in ['department', 'ministry', 'sector', 'directorate', 'service', 'about', 'our-team']:
            if kw in link_lower:
                dept_pages.add(link)
                break
    
    # Common department page paths
    common_paths = [
        '/departments', '/ministries', '/sectors', '/directorates',
        '/about/departments', '/about/divisions', '/services',
        '/county-departments', '/our-departments', '/our-services',
        '/government/departments', '/pages/departments',
        '/index.php/departments', '/home/departments',
    ]
    for path in common_paths:
        dept_pages.add(urllib.parse.urljoin(base_url, path))
    
    # Fetch department pages
    results = set()
    for url in dept_pages:
        html, _ = fetch(url)
        if html:
            r = extract_departments_from_content(html)
            results.update(r)
            r2 = extract_departments_from_nav(html, url)
            results.update(r2)
    
    return sorted(results)


def scrape_county_departments(name, urls):
    """Full deep scrape of a county's departments."""
    print(f"  {name}...", end=' ', flush=True)
    
    for url in urls:
        html, final_url = fetch(url)
        if html:
            break
    else:
        print("✗ UNREACHABLE")
        return None
    
    # Get all links from homepage
    all_links = extract_all_links(html, final_url)
    
    # Extract departments from multiple sources
    departments = set()
    departments.update(extract_departments_from_nav(html, final_url))
    departments.update(extract_departments_from_footer(html))
    departments.update(extract_departments_from_content(html))
    
    # Crawl department pages for deeper extraction
    dept_page_results = crawl_department_pages(final_url, all_links)
    departments.update(dept_page_results)
    
    # Clean up results
    cleaned = []
    seen = set()
    for d in sorted(departments, key=lambda x: len(x), reverse=True):
        d_clean = re.sub(r'\s+', ' ', d).strip()
        d_lower = d_clean.lower()
        
        # Skip noise
        if len(d_clean) < 5: continue
        if re.match(r'^\d+', d_clean): continue
        if any(s in d_lower for s in ['copyright', 'all rights', 'powered by', 'design by', 'click here', 'read more', 'home', 'contact']): continue
        
        # Deduplicate (skip if substring of existing)
        is_duplicate = False
        for existing in seen:
            if d_lower in existing or existing in d_lower:
                is_duplicate = True
                break
        
        if not is_duplicate:
            seen.add(d_lower)
            # Determine category
            cat = 'general'
            for kw in SECTOR_NAMES_KW:
                if kw in d_lower:
                    cat = kw
                    break
            cleaned.append({'name': d_clean[:100], 'category': cat[:30], 'source': final_url})
    
    print(f"✓ {len(cleaned)} departments")
    return cleaned


def main():
    OUTPUT.mkdir(parents=True, exist_ok=True)
    
    single = None
    for arg in sys.argv[1:]:
        if arg.startswith('--single='):
            single = arg.split('=', 1)[1]
    
    names = [n for n in COUNTY_SITES if not single or n.lower() == single.lower() or n.lower().replace(' ', '_') == single.lower()]
    print(f"Deep-scanning departments for {len(names)} counties...\n")
    
    results = {}
    def worker(name):
        depts = scrape_county_departments(name, COUNTY_SITES[name])
        return name, depts
    
    with ThreadPoolExecutor(max_workers=5) as ex:
        futures = {ex.submit(worker, n): n for n in names}
        for f in as_completed(futures):
            name, depts = f.result()
            if depts is not None:
                results[name] = depts
    
    # Save individual county files
    total = 0
    for name, depts in results.items():
        dir_name = name.lower().replace("'", '').replace(' ', '_').replace('&', 'and')
        county_dir = OUTPUT / dir_name
        county_dir.mkdir(parents=True, exist_ok=True)
        (county_dir / 'sectors.json').write_text(json.dumps(depts, indent=2))
        total += len(depts)
    
    print(f"\n{'='*50}")
    print(f"Scanned {len(results)} counties successfully")
    print(f"Total departments extracted: {total}")
    print(f"Average per county: {total/len(results):.1f}")


if __name__ == '__main__':
    main()
