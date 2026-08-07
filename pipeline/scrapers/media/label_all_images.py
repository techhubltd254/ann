#!/usr/bin/env python3
"""Label all images across project into kicc-images/"""
import os, sys, shutil, re
from pathlib import Path

BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(BASE, 'kicc-images')
if os.path.exists(OUT): shutil.rmtree(OUT)

def label_path(p):
    name = os.path.basename(p)
    stem = os.path.splitext(name)[0]
    folder = os.path.dirname(p).replace(BASE, '').replace('\\','/')
    
    if any(v in folder for v in ['tsavo','amphi','aberdare','lenana','shimba','courtyard','lawn','comesa']):
        label = stem.replace('kicc_','').replace('_',' ').replace('-',' ').title().strip()
        return ('venues', label)
    if 'county profile pics labeled' in folder:
        label = stem.replace('_',' ').replace("'",'').title() + ' County'
        return ('county-profiles', label)
    if any(s in name.lower() for s in ['catering','communication','technical','internet','security','faqimage','info-desk']):
        return ('services', stem.replace('kicc_','').replace('-',' ').title().strip())
    if 'storage/app/public/kicc' in folder or 'kicc-images/' in folder:
        if any(v in name for v in ['ANP_','DSC_','DSV_','IMX_','KICC-','G4']):
            return ('gallery', stem.replace('kicc_','').replace('_',' ').strip())
        if any(b in name.lower() for b in ['logo','tower-night','gate-day','hero','exterior','hall-interior','vip-lounge','magical-kenya']):
            return ('brand', stem.replace('_',' ').replace('-',' ').title().strip())
        return ('brand', stem.replace('_',' ').replace('-',' ').title().strip())
    if 'villa' in folder.lower():
        return ('3d-tour', stem.replace('_',' ').replace('-',' ').title())
    return ('gallery', stem.replace('_',' ').replace('-',' ').title())

count = 0
for root, dirs, files in os.walk(BASE):
    skip = ['vendor','.git','node_modules','.tools','kenya-3d-platform','kicc-images','.gitignore']
    if any(s in root for s in skip): continue
    for f in files:
        if not re.search(r'\.(jpg|jpeg|png|webp|gif)$', f, re.I): continue
        if 'kicc-logo' in f or 'kicc_Untitled' in f: continue
        src = os.path.join(root, f)
        cat, label = label_path(src)
        ext = os.path.splitext(f)[1].lower()
        d = os.path.join(OUT, cat)
        os.makedirs(d, exist_ok=True)
        dest = os.path.join(d, label + ext)
        if not os.path.exists(dest):
            shutil.copy2(src, dest)
            count += 1

for cat in ['venues','services','county-profiles','gallery','brand','3d-tour']:
    d = os.path.join(OUT, cat)
    n = len(os.listdir(d)) if os.path.exists(d) else 0
    print(f'{cat}: {n}')
print(f'Total: {count}')