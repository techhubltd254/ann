#!/usr/bin/env python3
"""
Scrape all images from KICC venue pages and gallery.
Saves labeled images into storage/app/public/kicc/.
"""
import os, re, sys, time, json, urllib.request, urllib.parse

BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(BASE, 'storage', 'app', 'public', 'kicc')
os.makedirs(OUT, exist_ok=True)

UA = {'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 KICC-Image-Scraper'}

# All venue pages with their proper labels
PAGES = [
    ('https://kicc.co.ke/tsavo-hall/', 'tsavo-hall'),
    ('https://kicc.co.ke/amphitheater/', 'amphitheatre'),
    ('https://kicc.co.ke/aberdares/', 'aberdares'),
    ('https://kicc.co.ke/lenana-hills/', 'lenana-hills'),
    ('https://kicc.co.ke/shimba-hills-room/', 'shimba-hills'),
    ('https://kicc.co.ke/courtyard/', 'courtyard'),
    ('https://kicc.co.ke/lawn/', 'lawn'),
    ('https://kicc.co.ke/upper-comesa/', 'upper-comesa'),
    ('https://kicc.co.ke/lower-comesa/', 'lower-comesa'),
    ('https://kicc.co.ke/raised-tree-area/', 'raised-tree-area'),
    ('https://kicc.co.ke/kicc-events/', 'events'),
    ('https://kicc.co.ke/view-tower-helipad/', 'helipad'),
    ('https://kicc.co.ke/parking-facilities/', 'parking'),
    ('https://kicc.co.ke/rooms-spaces/', 'rooms-spaces'),
    ('https://kicc.co.ke/videos-gallery/', 'videos'),
    ('https://kicc.co.ke/photos-gallery/', 'photos'),
    ('https://kicc.co.ke/mission-vision-mandate/', 'mission'),
    ('https://kicc.co.ke/about-us/', 'about'),
    ('https://kicc.co.ke/catering-services/', 'catering'),
    ('https://kicc.co.ke/audio-visual-equipment/', 'av-equipment'),
    ('https://kicc.co.ke/technical-support/', 'tech-support'),
    ('https://kicc.co.ke/event-planning-and-coordination/', 'event-planning'),
    ('https://kicc.co.ke/wi-fi-and-internet-access/', 'wifi'),
    ('https://kicc.co.ke/security-services-fire/', 'security'),
    ('https://kicc.co.ke/accessibility/', 'accessibility'),
    ('https://kicc.co.ke/tourist-information/', 'tourist-info'),
]

def fetch_page(url):
    req = urllib.request.Request(url, headers=UA)
    try:
        with urllib.request.urlopen(req, timeout=30) as r:
            return r.read().decode('utf-8', errors='replace')
    except Exception as e:
        print(f'  ERROR fetching {url}: {e}')
        return None

def extract_images(html, page_label):
    """Extract all image URLs from a page with context labels."""
    images = []
    # Match all img tags
    for m in re.finditer(r'<img[^>]+src="([^"]+)"[^>]*>', html, re.I):
        src = m[0]
        url_match = re.search(r'src="([^"]+)"', src)
        if not url_match:
            continue
        url = url_match.group(1)
        if url.startswith('data:') or '.svg' in url or url.endswith('.svg'):
            continue
        # Get alt text or title for labeling
        alt = re.search(r'alt="([^"]*)"', src)
        alt_text = alt.group(1).strip() if alt else ''
        title = re.search(r'title="([^"]*)"', src)
        title_text = title.group(1).strip() if title else ''
        label = alt_text or title_text or page_label
        images.append({'url': url, 'label': label, 'page': page_label})
    return images

def download_image(url, dest_path):
    """Download image from URL (handle relative and WP media paths)."""
    # Handle relative URLs
    if url.startswith('//'):
        url = 'https:' + url
    elif url.startswith('/'):
        url = 'https://kicc.co.ke' + url
    elif not url.startswith('http'):
        url = 'https://kicc.co.ke/' + url.lstrip('/')
    
    # Only download from kicc.co.ke or uploads
    if 'kicc.co.ke' not in url and 'uploads' not in url:
        return False
    # Skip logos, icons, small decorative images
    if any(k in url.lower() for k in ['logo', 'icon', 'placeholder', 'member', 'certif', 'icca', 'aipc', 'unwto', 'mpi', 'sgs', 'social', 'facebook', 'twitter', 'instagram']):
        return False
    
    # Clean URL (remove query params for file extension detection)
    clean_url = url.split('?')[0]
    ext = os.path.splitext(clean_url)[1].lower()
    if ext not in ('.jpg', '.jpeg', '.png', '.webp', '.gif'):
        ext = '.jpg'  # default
    
    req = urllib.request.Request(url, headers=UA)
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            data = r.read()
        if len(data) < 5000:  # skip tiny files (icons, thumbs)
            return False
        
        # Generate unique filename from URL hash
        fname = f"kicc_{os.path.basename(clean_url).split('.')[0][:40]}{ext}"
        fpath = os.path.join(OUT, fname)
        if os.path.exists(fpath):
            return False
        
        with open(fpath, 'wb') as f:
            f.write(data)
        print(f'  SAVED {fname} ({len(data)/1024:.0f} KB)')
        return True
    except Exception as e:
        print(f'  FAIL {url[:60]}: {e}')
        return False

def main():
    all_images = []
    total_downloaded = 0
    
    for url, label in PAGES:
        print(f'\n== {label} ==')
        html = fetch_page(url)
        if not html:
            continue
        imgs = extract_images(html, label)
        print(f'  Found {len(imgs)} images')
        all_images.extend(imgs)
        
        for img in imgs:
            if download_image(img['url'], OUT):
                total_downloaded += 1
            time.sleep(0.2)
    
    print(f'\n=== Done: {total_downloaded} new images downloaded ===')
    
    # Save manifest
    manifest = os.path.join(OUT, '_gallery_manifest.json')
    with open(manifest, 'w') as f:
        json.dump(all_images, f, indent=1)
    print(f'Manifest saved to {manifest}')

if __name__ == '__main__':
    main()
