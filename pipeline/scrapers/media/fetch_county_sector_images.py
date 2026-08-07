#!/usr/bin/env python3
"""
Fetch representative sector images for each Kenyan county from Wikimedia Commons
(freely-licensed images with attribution metadata).

Usage:
    python3 scrips/fetch_county_sector_images.py [--county slug] [--limit N] [--dry-run]

Reads the local sqlite DB for real entity names (attractions, hotels, etc.) to build
high-quality search queries, falling back to "{County} Kenya {sector keyword}".

Outputs:
    storage/app/public/counties/{slug}/{route}.jpeg
    storage/app/public/counties/{slug}/attributions.json
"""

import argparse
import json
import os
import sqlite3
import sys
import time
import urllib.parse
import urllib.request

BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DB_PATH = os.path.join(BASE, 'database', 'database.sqlite')
OUT_BASE = os.path.join(BASE, 'storage', 'app', 'public', 'counties')
COMMONS_API = 'https://commons.wikimedia.org/w/api.php'
UA = {'User-Agent': 'KICC-Platform/1.0 (https://kicctest.org; county image collector)'}

# sector route -> search keyword (entity names in DB are fictional seed data — don't use)
SECTORS = {
    'tourism':      'safari park wildlife scenery',
    'hotels':       'hotel lodge resort beach',
    'products':     'market crafts produce',
    'institutions': 'university college school',
    'farms':        'farm tea coffee agriculture',
    'transport':    'road highway bus matatu railway',
    'health':       'hospital clinic',
    'culture':      'traditional dancers village culture',
}

MIN_WIDTH = 1000

# book scans / archive noise that pollute Commons search results
BAD_TITLE = ('guide to', 'journal', 'travels in', 'history of', '1872', '1881',
             '1902', '1907', '1910', '1920', 'yellowstone', 'zanzibar-')


def api_get(params):
    url = COMMONS_API + '?' + urllib.parse.urlencode(params)
    req = urllib.request.Request(url, headers=UA)
    with urllib.request.urlopen(req, timeout=25) as r:
        return json.load(r)


def commons_search(query, limit=8):
    params = {
        'action': 'query', 'format': 'json',
        'generator': 'search', 'gsrsearch': f'filemime:jpeg {query}',
        'gsrnamespace': '6', 'gsrlimit': str(limit),
        'prop': 'imageinfo', 'iiprop': 'url|size|extmetadata', 'iiurlwidth': '1600',
    }
    try:
        d = api_get(params)
    except Exception as e:
        print(f'    search error: {e}')
        return []
    pages = (d.get('query', {}) or {}).get('pages', {}) or {}
    results = sorted(pages.values(), key=lambda p: p.get('index', 99))
    out = []
    for p in results:
        ii = (p.get('imageinfo') or [{}])[0]
        meta = ii.get('extmetadata', {}) or {}
        out.append({
            'title': p.get('title', ''),
            'thumb': ii.get('thumburl') or ii.get('url'),
            'width': ii.get('thumbwidth') or ii.get('width') or 0,
            'height': ii.get('thumbheight') or ii.get('height') or 0,
            'license': (meta.get('LicenseShortName', {}) or {}).get('value', '?'),
            'artist': strip_html((meta.get('Artist', {}) or {}).get('value', '?')),
            'page': ii.get('descriptionurl', ''),
        })
    return out


def strip_html(s):
    import re
    return re.sub(r'<[^>]+>', '', s or '').strip()[:120]


def pick(results, county_name, used, keyword=''):
    """Score-based pick: topical relevance (keyword words in title) beats
    county-name match, both require 'kenya' in title and no archive noise."""
    cn = county_name.lower().split()[0]
    kws = [w for w in keyword.lower().split() if len(w) > 3]
    best, best_score = None, -1
    for r in results:
        t = r['title'].lower()
        if 'kenya' not in t or any(b in t for b in BAD_TITLE) or r['title'] in used:
            continue
        score = sum(3 for w in kws if w in t)
        if cn in t:
            score += 2
        if r['width'] >= MIN_WIDTH and r['width'] >= r['height']:
            score += 1
        if score > best_score:
            best, best_score = r, score
    return best


def download(url, dest):
    req = urllib.request.Request(url, headers=UA)
    with urllib.request.urlopen(req, timeout=60) as r, open(dest, 'wb') as f:
        f.write(r.read())


def county_entities(db, table, name_col, county_id):
    try:
        cur = db.execute(f'SELECT {name_col} FROM {table} WHERE county_id = ? LIMIT 4', (county_id,))
        return [r[0] for r in cur.fetchall() if r[0]]
    except sqlite3.Error:
        return []


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--county', help='only this county slug')
    ap.add_argument('--limit', type=int, default=0, help='max counties to process')
    ap.add_argument('--dry-run', action='store_true')
    args = ap.parse_args()

    db = sqlite3.connect(DB_PATH)
    db.row_factory = sqlite3.Row
    counties = db.execute('SELECT id, slug, name FROM counties ORDER BY id').fetchall()

    processed = 0
    for c in counties:
        slug, name, cid = c['slug'], c['name'], c['id']
        if args.county and slug != args.county:
            continue
        out_dir = os.path.join(OUT_BASE, slug)
        os.makedirs(out_dir, exist_ok=True)
        attrs_path = os.path.join(out_dir, 'attributions.json')
        attrs = {}
        if os.path.exists(attrs_path):
            attrs = json.load(open(attrs_path))

        needed = [r for r in SECTORS if not os.path.exists(os.path.join(out_dir, f'{r}.jpeg'))]
        if not needed:
            print(f'== {name}: complete, skipping')
            continue

        print(f'== {name} ({slug}) — need {len(needed)} sectors')
        used = set()
        for route in needed:
            keyword = SECTORS[route]
            queries = [f'{name} Kenya {keyword}', f'Kenya {keyword}', f'{name} County Kenya']
            chosen = None
            for q in queries:
                results = commons_search(q)
                time.sleep(0.3)
                chosen = pick(results, name, used, keyword)
                if chosen:
                    used.add(chosen['title'])
                    print(f'  {route:13s} <- "{q}" => {chosen["title"][:60]} [{chosen["license"]}]')
                    break
            if not chosen:
                print(f'  {route:13s} !! no image found')
                continue
            if not args.dry_run:
                dest = os.path.join(out_dir, f'{route}.jpeg')
                try:
                    download(chosen['thumb'], dest)
                    time.sleep(0.2)
                except Exception as e:
                    print(f'    download error: {e}')
                    continue
                attrs[route] = {k: chosen[k] for k in ('title', 'license', 'artist', 'page')}
        if not args.dry_run and attrs:
            json.dump(attrs, open(attrs_path, 'w'), indent=1, ensure_ascii=False)
        processed += 1
        if args.limit and processed >= args.limit:
            break

    print('Done.')


if __name__ == '__main__':
    main()
