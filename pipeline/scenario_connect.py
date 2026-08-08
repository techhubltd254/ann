#!/usr/bin/env python3
"""
Scenario connector — group photos/videos of the SAME location/scene.

Method: perceptual hashing (pHash) + Hamming distance. Images of the same
place (even across bursts/angles/footage) hash close together. Each connected
group = one scenario = one 3D source (multi-angle burst ready for splat/video).

Usage: scenario_connect.py <dir_of_images> [threshold]
Output: groups.json  { "scenario_01": [img, img, ...], ... }
"""
import json
import subprocess
import sys
from pathlib import Path


def phash(path: str, size: int = 32) -> str:
    """Perceptual hash via ImageMagick: resize->gray->dct, threshold by median."""
    out = subprocess.run(
        ["convert", path, "-resize", f"{size}x{size}!", "-colorspace", "gray",
         "-dct", "0,0", "-format", "%[fx:mean]", "info:"],
        capture_output=True, text=True,
    )
    # simpler robust pHash via pixel signature
    out = subprocess.run(
        ["convert", path, "-resize", "16x16!", "-colorspace", "gray", "-depth", "8", "gray:-"],
        capture_output=True,
    )
    data = out.stdout
    if len(data) < 256:
        return "0" * 64
    px = list(data[:256])
    med = sorted(px)[128]
    bits = ''.join('1' if p > med else '0' for p in px)
    return hex(int(bits, 2))[2:].zfill(64)


def hamming(a: str, b: str) -> int:
    return bin(int(a, 16) ^ int(b, 16)).count('1')


def main():
    d = Path(sys.argv[1])
    threshold = int(sys.argv[2]) if len(sys.argv) > 2 else 26
    imgs = sorted([str(p) for p in d.glob("*.jpg")] + [str(p) for p in d.glob("*.JPG")])
    print(f"[connect] {len(imgs)} images")

    hashes = {}
    for i, f in enumerate(imgs):
        hashes[f] = phash(f)
        if i % 50 == 0:
            print(f"  hashed {i}/{len(imgs)}", flush=True)

    # union-find grouping
    parent = {f: f for f in imgs}

    def find(x):
        while parent[x] != x:
            parent[x] = parent[parent[x]]
            x = parent[x]
        return x

    def union(a, b):
        ra, rb = find(a), find(b)
        if ra != rb:
            parent[ra] = rb

    keys = list(hashes)
    for i in range(len(keys)):
        for j in range(i + 1, len(keys)):
            if hamming(hashes[keys[i]], hashes[keys[j]]) <= threshold:
                union(keys[i], keys[j])

    groups = {}
    for f in imgs:
        groups.setdefault(find(f), []).append(f)

    out = {}
    n = 0
    for g in groups.values():
        if len(g) >= 2:  # a scenario needs 2+ angles
            n += 1
            out[f"scenario_{n:02d}"] = g

    dest = d / "scenarios.json"
    dest.write_text(json.dumps(out, indent=2))
    print(f"[connect] {n} scenarios (2+ frames each) -> {dest}")


if __name__ == "__main__":
    main()
