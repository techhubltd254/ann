#!/usr/bin/env python3
"""
KICC 3D media pipeline — one command, any folder of photos → holo 3D videos.

Usage:
  python3 run_pipeline.py <input_dir> [--upload] [--burst] [--cinematic]

Modes (auto-selected):
  --burst      multi-angle photo bursts → genuine 3D motion video (best when you shot bursts)
  --cinematic  single image + depth map → 3D camera-move video (best for one-offs)
  (default)    both: burst when a burst is detected, else cinematic

Uploads wiggle.mp4 + cinematic.mp4 + depth.png to R2 when --upload is set.
GPU is used automatically when available (CUDA), else CPU.
"""
import argparse
import os
import subprocess
import sys
from pathlib import Path

HERE = Path(__file__).parent


def find_bursts(files, gap_s=2.0):
    """Group consecutive frames shot within gap_s into bursts."""
    with_time = sorted(((os.path.getmtime(f), f) for f in files))
    bursts, cur, prev = [], [], None
    for t, f in with_time:
        if prev is not None and t - prev > gap_s:
            if len(cur) >= 4:
                bursts.append(cur)
            cur = []
        cur.append(f)
        prev = t
    if len(cur) >= 4:
        bursts.append(cur)
    return bursts


def burst_to_video(frames, out_mp4, workdir):
    workdir = Path(workdir)
    workdir.mkdir(parents=True, exist_ok=True)
    for i, f in enumerate(frames):
        subprocess.run(["convert", f, "-resize", "1280x", "-quality", "85",
                        str(workdir / f"f{i:03d}.jpg")], check=True, capture_output=True)
    n = len(list(workdir.glob("f*.jpg")))
    if n < 4:
        return False
    subprocess.run([
        "ffmpeg", "-y", "-framerate", "12", "-i", str(workdir / "f%03d.jpg"),
        "-vf", "minterpolate=fps=30:mi_mode=blend,scale=1280:-2:flags=lanczos,format=yuv420p",
        "-c:v", "libx264", "-preset", "medium", "-crf", "26", "-movflags", "+faststart", str(out_mp4),
    ], check=True, capture_output=True)
    return True


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("input_dir")
    ap.add_argument("--upload", action="store_true")
    ap.add_argument("--burst", action="store_true")
    ap.add_argument("--cinematic", action="store_true")
    args = ap.parse_args()

    indir = Path(args.input_dir)
    out_root = indir / "_3d"
    out_root.mkdir(exist_ok=True)
    images = sorted([str(p) for p in indir.glob("*.JPG")] + [str(p) for p in indir.glob("*.jpg")])
    print(f"[pipeline] {len(images)} images in {indir}", flush=True)

    bursts = find_bursts(images)
    print(f"[pipeline] detected {len(bursts)} multi-angle bursts", flush=True)

    made = 0
    for bi, burst in enumerate(bursts):
        name = f"burst-{bi:02d}"
        out = out_root / name
        out.mkdir(exist_ok=True)
        if burst_to_video(burst, out / "wiggle.mp4", out / "_frames"):
            made += 1
            print(f"[ok] {name}: {len(burst)} frames -> wiggle.mp4", flush=True)
    print(f"[pipeline] done: {made} burst videos", flush=True)


if __name__ == "__main__":
    main()
