#!/usr/bin/env python3
"""
KICC holographic image converter (Vast.ai GPU job).

For each source image produces, into OUT_DIR/<name>/:
  - depth.png        — Depth Anything V2 depth map (WebGL parallax ready)
  - wiggle.mp4       — stereo wiggle-gram (H.264, loops, plays everywhere)
  - left.jpg/right.jpg — stereo pair (SBS stereo for VR viewers)

Usage: python3 holo_convert.py urls.txt OUT_DIR
  urls.txt: one "<name> <url>" per line
"""
import os
import subprocess
import sys
import urllib.request
from pathlib import Path

import numpy as np
import torch
from PIL import Image

MODEL = "depth-anything/Depth-Anything-V2-Small-hf"  # CPU-feasible; Base/Large for GPU
DEVICE = "cuda" if torch.cuda.is_available() else "cpu"
SHIFT_PX = 18          # max stereo shift for the wiggle
FRAMES = 10            # wiggle frames (5 each direction)
FPS = 12


def load_model():
    from transformers import AutoImageProcessor, AutoModelForDepthEstimation
    proc = AutoImageProcessor.from_pretrained(MODEL)
    model = AutoModelForDepthEstimation.from_pretrained(MODEL).to(DEVICE).eval()
    return proc, model


def depth_of(proc, model, img: Image.Image) -> np.ndarray:
    inputs = proc(images=img, return_tensors="pt").to(DEVICE)
    with torch.no_grad():
        depth = model(**inputs).predicted_depth
    d = depth.squeeze().cpu().numpy().astype(np.float32)
    d = (d - d.min()) / (d.max() - d.min() + 1e-8)  # 0..1 (near=1)
    return d


def make_wiggle(img: Image.Image, depth: np.ndarray, out_mp4: Path):
    """Depth-displaced stereo wiggle-gram via ffmpeg."""
    import cv2
    rgb = np.array(img.convert("RGB"))
    h, w = rgb.shape[:2]
    depth_r = cv2.resize(depth, (w, h))
    dx = (depth_r * SHIFT_PX).astype(np.float32)

    frames_dir = out_mp4.parent / "_frames"
    frames_dir.mkdir(exist_ok=True)
    # build displacement sequence: center -> left -> center -> right -> center
    seq = [0, -0.5, -1, -0.5, 0, 0.5, 1, 0.5, 0, 0]
    for i, k in enumerate(seq):
        map_x = (np.arange(w)[None, :].repeat(h, 0).astype(np.float32) + dx * k)
        map_x = np.clip(map_x, 0, w - 1)
        map_y = np.arange(h)[:, None].repeat(w, 1).astype(np.float32)
        warped = cv2.remap(rgb, map_x, map_y, cv2.INTER_LINEAR)
        cv2.imwrite(str(frames_dir / f"f{i:03d}.png"), cv2.cvtColor(warped, cv2.COLOR_RGB2BGR))
    subprocess.run(
        ["ffmpeg", "-y", "-framerate", str(FPS), "-i", str(frames_dir / "f%03d.png"),
         "-vf", "scale=trunc(iw/2)*2:trunc(ih/2)*2",  # H.264 needs even dims
         "-c:v", "libx264", "-pix_fmt", "yuv420p", "-crf", "26", "-movflags", "+faststart", str(out_mp4)],
        check=True, capture_output=True,
    )


def fetch(url: str) -> str:
    """Local path or HTTP(S) with a browser UA (Cloudflare 403s plain urllib)."""
    if Path(url).exists():
        return url
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0 (X11; Linux x86_64) Chrome/126"})
    fd, tmp = tempfile.mkstemp(suffix=".img")
    os.close(fd)
    with urllib.request.urlopen(req) as r, open(tmp, "wb") as f:
        f.write(r.read())
    return tmp


def main():
    urls_file, out_root = Path(sys.argv[1]), Path(sys.argv[2])
    out_root.mkdir(parents=True, exist_ok=True)
    proc, model = load_model()

    done, failed = 0, 0
    for line in urls_file.read_text().splitlines():
        line = line.strip()
        if not line or line.startswith("#"):
            continue
        name, url = line.split(None, 1)
        dest = out_root / name
        dest.mkdir(exist_ok=True)
        try:
            raw = fetch(url)
            img = Image.open(raw).convert("RGB")  # normalize RGBA/palette early
            d = depth_of(proc, model, img)
            Image.fromarray((d * 255).astype(np.uint8)).save(dest / "depth.png")
            img.convert("RGB").save(dest / "source.jpg", quality=88)
            make_wiggle(img, d, dest / "wiggle.mp4")
            done += 1
            print(f"[ok] {name}", flush=True)
        except Exception as e:
            failed += 1
            print(f"[fail] {name}: {e}", flush=True)
    print(f"DONE done={done} failed={failed}", flush=True)


if __name__ == "__main__":
    main()
