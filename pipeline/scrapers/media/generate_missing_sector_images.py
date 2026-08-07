#!/usr/bin/env python3
"""
Generate missing county sector images via OpenRouter GPT-5 Image.
Reads county data from SQLite, crafts per-sector prompts, and
fills gaps with AI-generated imagery. Cost ~$0.17/image.
"""
import base64, json, os, sqlite3, sys, time, urllib.request, urllib.parse

BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DB = os.path.join(BASE, 'database', 'database.sqlite')
OUT = os.path.join(BASE, 'storage', 'app', 'public', 'counties')
API_KEY = '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'
API = 'https://openrouter.ai/api/v1/images/generations'
COST_PER = 0.033

MODEL = 'openai/gpt-5-image-mini'

SECTORS = {
    'tourism': 'tourism attraction, landmark, scenic view',
    'hotels': 'hotel, resort, accommodation, lodge',
    'products': 'local products, crafts, market, produce',
    'institutions': 'school, university, institution, college',
    'farms': 'farm, agricultural land, crops, livestock',
    'transport': 'road, highway, transport, bus, railway',
    'health': 'hospital, clinic, health facility',
    'culture': 'cultural site, traditional dancers, heritage',
}

PHOTO_STYLES = {
    'tourism': 'Beautiful landscape photography of',
    'hotels': 'Professional architectural photography of a hotel in',
    'products': 'High-quality product photography, local produce from',
    'institutions': 'Educational institution exterior photography in',
    'farms': 'Aerial agricultural landscape photography in',
    'transport': 'Transport infrastructure photography in',
    'health': 'Modern healthcare facility exterior in',
    'culture': 'Cultural heritage photography, traditional scene in',
}


def generate_image(prompt, out_path, max_retries=2):
    payload = json.dumps({
        'model': MODEL,
        'prompt': f'{prompt}, high quality photograph, National Geographic style, 4K, bright daylight, photorealistic, no text, no watermark, safe for work',
        'size': '1024x1024', 'n': 1,
    }).encode()
    for attempt in range(max_retries):
        try:
            req = urllib.request.Request(API, data=payload,
                headers={'Authorization': f'Bearer {API_KEY}', 'Content-Type': 'application/json'},
                method='POST')
            with urllib.request.urlopen(req, timeout=120) as r:
                d = json.load(r)
            b64 = d['data'][0].get('b64_json')
            if not b64:
                print(f'    NO b64 in response: {list(d.keys())}')
                return False
            with open(out_path, 'wb') as f:
                f.write(base64.b64decode(b64))
            cost = d.get('usage', {}).get('cost', COST_PER)
            print(f'    Saved ({os.path.getsize(out_path)/1024:.0f} KB, ${cost:.2f})')
            time.sleep(0.5)
            return True
        except Exception as e:
            if attempt < max_retries - 1:
                print(f'    Retry {attempt+1}: {e}')
                time.sleep(5)
            else:
                print(f'    FAILED: {e}')
                return False
    return False


def main():
    db = sqlite3.connect(DB)
    rows = db.execute('SELECT id, slug, name FROM counties ORDER BY id').fetchall()

    total_generated = 0
    total_cost = 0
    pending = []

    for cid, slug, name in rows:
        out_dir = os.path.join(OUT, slug)
        os.makedirs(out_dir, exist_ok=True)
        for route, sector_desc in SECTORS.items():
            dest = os.path.join(out_dir, f'{route}.jpeg')
            if os.path.exists(dest):
                continue
            style = PHOTO_STYLES.get(route, '')
            prompt = f'{style} {name} County, Kenya, {sector_desc}'
            print(f'{name:15s} {route:13s} -> generating...')
            sys.stdout.flush()
            if generate_image(prompt, dest):
                total_generated += 1
                total_cost += COST_PER
                pending.append((slug, route))
            else:
                pending.append((slug, route, 'FAILED'))
            time.sleep(1)

    db.close()
    print(f'\nDone: {total_generated} images generated, cost ~${total_cost:.2f}')
    if pending:
        print(f'Pending/failed: {len(pending)}')
        for p in pending[:10]:
            print(f'  {p}')


if __name__ == '__main__':
    main()