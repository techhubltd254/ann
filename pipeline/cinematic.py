#!/usr/bin/env python3
"""
Cinematic 3D parallax video from a photo + its depth map.

Animates a slow camera dolly/pan with depth-weighted displacement — foreground
shifts more than background — producing a true "move-through-the-scene" 3D feel
(the spline-splat look) from a single 2D photo. Smooth ease-in/out, seamless loop.

Usage: cinematic.py <image> <depth.png> <out.mp4> [seconds]
"""
import sys
from pathlib import Path

import cv2
import numpy as np

FPS = 30


def ease(t):  # smoothstep 0..1
    return t * t * (3 - 2 * t)


def render(img_path, depth_path, out_path, seconds=6.0, max_shift=26):
    out_path = Path(out_path)
    img = cv2.imread(str(img_path))
    h, w = img.shape[:2]
    depth = cv2.imread(str(depth_path), cv2.IMREAD_GRAYSCALE).astype(np.float32) / 255.0
    depth = cv2.resize(depth, (w, h))
    depth = cv2.GaussianBlur(depth, (0, 0), 5)

    n = int(FPS * seconds)
    yy, xx = np.mgrid[0:h, 0:w].astype(np.float32)

    # camera path: gentle horizontal dolly with slight vertical breathing + zoom
    frames = []
    for i in range(n):
        t = i / n
        phase = 2 * np.pi * t
        dx = np.sin(phase) * max_shift * depth            # parallax by depth
        dy = np.cos(phase) * (max_shift * 0.35) * depth
        zoom = 1.0 + 0.03 * np.sin(phase)                 # subtle push-in/out
        mx = (xx + dx).astype(np.float32)
        my = (yy + dy).astype(np.float32)
        # zoom about center
        cx, cy = w / 2, h / 2
        mx = ((mx - cx) * zoom + cx).astype(np.float32)
        my = ((my - cy) * zoom + cy).astype(np.float32)
        frame = cv2.remap(img, np.clip(mx, 0, w - 1), np.clip(my, 0, h - 1), cv2.INTER_LINEAR)
        frames.append(frame)

    tmp = out_path.with_suffix(".raw.mp4")
    writer = cv2.VideoWriter(str(tmp), cv2.VideoWriter_fourcc(*"mp4v"), FPS, (w, h))
    for f in frames:
        writer.write(f)
    writer.release()

    # re-encode H.264 even-dims + faststart for web
    import subprocess
    subprocess.run([
        "ffmpeg", "-y", "-i", str(tmp),
        "-vf", "scale=trunc(iw/2)*2:trunc(ih/2)*2",
        "-c:v", "libx264", "-preset", "medium", "-crf", "26",
        "-pix_fmt", "yuv420p", "-movflags", "+faststart", str(out_path),
    ], check=True, capture_output=True)
    tmp.unlink(missing_ok=True)


if __name__ == "__main__":
    img, depth, out = sys.argv[1], sys.argv[2], sys.argv[3]
    secs = float(sys.argv[4]) if len(sys.argv) > 4 else 6.0
    render(img, depth, out, secs)
    print(f"[ok] {out}")
