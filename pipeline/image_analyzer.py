"""Fast Pillow-based image analysis for county photos.

Extracts: dimensions, dominant colors, brightness, contrast,
scene classification hints (water, vegetation, buildings, sky).

Pure Python stdlib + Pillow. No OpenCV, no numpy, no matplotlib.
Subsampled for speed on large images.
"""

from __future__ import annotations

import json
import struct
from collections import Counter
from math import sqrt
from pathlib import Path
from typing import Any

from PIL import Image


def probe_image(path: str | Path) -> dict[str, Any]:
    """Fast binary probe — reads image metadata without full decode."""
    path = Path(path)
    data = path.read_bytes()
    img_type, width, height = _detect_format(data)

    warnings = []
    if width is None or height is None:
        warnings.append("could not read dimensions")
    else:
        aspect = width / height if height else None
        if width < 512 or height < 512:
            warnings.append("low resolution")
        if aspect and (aspect > 3.0 or aspect < 0.33):
            warnings.append("extreme aspect ratio")

    return {
        "path": str(path),
        "type": img_type,
        "bytes": len(data),
        "width": width,
        "height": height,
        "aspect_ratio": round(width / height, 4) if width and height else None,
        "warnings": warnings,
    }


def analyze_image(path: str | Path, max_pixels: int = 250000) -> dict[str, Any]:
    """Full Pillow-based analysis with subsampling for speed.

    Opens the image, extracts color palette, brightness, contrast,
    and scene-type heuristics. Subsampled to max_pixels for speed.
    """
    probe = probe_image(path)
    img = Image.open(path).convert("RGB")
    w, h = img.size

    pixels = _sample_pixels(img, max_pixels)
    total = len(pixels)

    dominant_colors = _extract_palette(pixels, n=5)
    brightness = _brightness(pixels)
    contrast = _contrast(pixels, brightness)

    water_score = _detect_blue_region(pixels)
    green_score = _detect_green_region(pixels)
    sky_score = _detect_sky_region(pixels)
    warm_score = _detect_warm_region(pixels)

    scene_type = _classify_scene(
        water_score, green_score, sky_score, warm_score, brightness, w, h
    )

    palette_hex = [_rgb_to_hex(c) for c in dominant_colors]

    return {
        **probe,
        "dominant_colors": palette_hex,
        "brightness": round(brightness, 4),
        "contrast": round(contrast, 4),
        "scene_type": scene_type,
        "has_water": water_score > 0.15,
        "has_vegetation": green_score > 0.20,
        "has_sky": sky_score > 0.15,
        "samples": total,
        "scores": {
            "water": round(water_score, 4),
            "vegetation": round(green_score, 4),
            "sky": round(sky_score, 4),
            "warm": round(warm_score, 4),
        },
    }


def _sample_pixels(img: Image.Image, max_pixels: int) -> list[tuple[int, int, int]]:
    """Subsample pixels evenly across the image for fast analysis."""
    w, h = img.size
    total = w * h
    step = max(1, int(sqrt(total / max_pixels)))
    pixels = []
    pix = img.load()
    for y in range(0, h, step):
        for x in range(0, w, step):
            c = pix[x, y]
            pixels.append(c)
    return pixels


def _detect_format(data: bytes) -> tuple[str | None, int | None, int | None]:
    if data.startswith(b"\xff\xd8"):
        w, h = _jpeg_size(data)
        return "jpeg", w, h
    if data.startswith(b"\x89PNG\r\n\x1a\n"):
        if len(data) >= 24:
            w, h = struct.unpack(">II", data[16:24])
            return "png", w, h
    if data[:6] in {b"GIF87a", b"GIF89a"}:
        if len(data) >= 10:
            w, h = struct.unpack("<HH", data[6:10])
            return "gif", w, h
    if len(data) >= 12 and data[:4] == b"RIFF" and data[8:12] == b"WEBP":
        return "webp", None, None
    if data.startswith(b"BM") and len(data) >= 26:
        w = struct.unpack("<I", data[18:22])[0]
        h = abs(struct.unpack("<i", data[22:26])[0])
        return "bmp", w, h
    if data[:4] in {b"II*\x00", b"MM\x00*"}:
        w, h = _tiff_size(data)
        return "tiff", w, h
    return None, None, None


def _jpeg_size(data: bytes) -> tuple[int | None, int | None]:
    idx = 2
    while idx + 9 < len(data):
        if data[idx] != 0xFF:
            idx += 1
            continue
        marker = data[idx + 1]
        idx += 2
        if marker in {0xD8, 0xD9}:
            continue
        if idx + 2 > len(data):
            return None, None
        length = struct.unpack(">H", data[idx: idx + 2])[0]
        if length < 2 or idx + length > len(data):
            return None, None
        if marker in {0xC0, 0xC1, 0xC2, 0xC3, 0xC5, 0xC6, 0xC7, 0xC9, 0xCA, 0xCB, 0xCD, 0xCE, 0xCF}:
            if length >= 7:
                h, w = struct.unpack(">HH", data[idx + 3: idx + 7])
                return w, h
        idx += length
    return None, None


def _tiff_size(data: bytes) -> tuple[int | None, int | None]:
    if len(data) < 8:
        return None, None
    endian = "<" if data[:4] == b"II*\x00" else ">" if data[:4] == b"MM\x00*" else None
    if not endian:
        return None, None
    offset = struct.unpack(f"{endian}I", data[4:8])[0]
    if offset + 2 > len(data):
        return None, None
    entries = struct.unpack(f"{endian}H", data[offset: offset + 2])[0]
    w = h = None
    cursor = offset + 2
    for _ in range(entries):
        if cursor + 12 > len(data):
            return None, None
        tag, vtype, count, raw = struct.unpack(f"{endian}HHI", data[cursor: cursor + 12])
        if vtype in {3, 4} and count == 1:
            val = raw if vtype == 4 else raw & 0xFFFF
            if tag == 256:
                w = val
            elif tag == 257:
                h = val
        cursor += 12
    return w, h


def _extract_palette(pixels: list[tuple[int, int, int]], n: int = 5) -> list[tuple[int, int, int]]:
    quantized = [(r // 32 * 32, g // 32 * 32, b // 32 * 32) for r, g, b in pixels]
    counter = Counter(quantized)
    return [color for color, _ in counter.most_common(n)]


def _brightness(pixels: list[tuple[int, int, int]]) -> float:
    total = sum(0.299 * r + 0.587 * g + 0.114 * b for r, g, b in pixels)
    return total / len(pixels) / 255 if pixels else 0


def _contrast(pixels: list[tuple[int, int, int]], mean_brightness: float) -> float:
    if not pixels:
        return 0
    var = sum(
        ((0.299 * r + 0.587 * g + 0.114 * b) / 255 - mean_brightness) ** 2
        for r, g, b in pixels
    )
    return sqrt(var / len(pixels))


def _luminance(r: int, g: int, b: int) -> float:
    return 0.299 * r + 0.587 * g + 0.114 * b


def _detect_blue_region(pixels: list[tuple[int, int, int]]) -> float:
    """Water: deep blue-dominant pixels that are NOT sky.
    Water is darker (luminance < 120), more saturated blue.
    Sky is bright (luminance > 170), lighter blue.
    """
    count = 0
    for r, g, b in pixels:
        lum = _luminance(r, g, b)
        is_water = b > r + 10 and b > g + 5 and b > 40 and lum < 140
        if is_water:
            count += 1
    return count / len(pixels) if pixels else 0


def _detect_green_region(pixels: list[tuple[int, int, int]]) -> float:
    """Vegetation: green-dominant pixels with moderate luminance."""
    count = sum(1 for r, g, b in pixels if g > r + 10 and g > b + 5 and g > 60 and _luminance(r, g, b) < 200)
    return count / len(pixels) if pixels else 0


def _detect_sky_region(pixels: list[tuple[int, int, int]]) -> float:
    """Sky: bright blue-ish pixels with high luminance."""
    count = 0
    for r, g, b in pixels:
        lum = _luminance(r, g, b)
        is_sky_blue = b > r and b > g and lum > 160
        is_warm_sky = r > 180 and g > 140 and b > 100 and lum > 170
        if is_sky_blue or is_warm_sky:
            count += 1
    return count / len(pixels) if pixels else 0


def _detect_warm_region(pixels: list[tuple[int, int, int]]) -> float:
    """Warm tones: red/orange/yellow dominant."""
    count = sum(1 for r, g, b in pixels if r > b + 20 and r > 120 and g > 80)
    return count / len(pixels) if pixels else 0


def _classify_scene(
    water: float, green: float, sky: float, warm: float,
    brightness: float, w: int, h: int,
) -> str:
    if water > 0.15:
        return "coastal"
    if green > 0.40:
        return "rural"
    if green > 0.25 and sky > 0.20:
        return "rural"
    if warm > 0.30 and brightness > 0.55:
        return "arid"
    if green > 0.15:
        return "rural"
    if warm > 0.25 and brightness < 0.45:
        return "arid"
    if sky > 0.50:
        return "rural"
    return "urban"


def _rgb_to_hex(rgb: tuple[int, int, int]) -> str:
    return f"#{rgb[0]:02x}{rgb[1]:02x}{rgb[2]:02x}"


if __name__ == "__main__":
    import sys, time
    path = sys.argv[1] if len(sys.argv) > 1 else "."
    p = Path(path)
    if p.is_dir():
        results = []
        t0 = time.time()
        for f in sorted(p.glob("*.*g")):
            tr = time.time()
            result = analyze_image(f)
            results.append(result)
            elapsed = time.time() - tr
            print(
                f"{elapsed:6.2f}s  "
                f"{result.get('width', '?'):>6}×{result.get('height', '?'):<6} "
                f"{result['scene_type']:<10} "
                f"{result.get('samples', 0):>6}px "
                f"{f.name}"
            )
        total = time.time() - t0
        print(f"\n{len(results)} images in {total:.2f}s ({total/len(results):.2f}s avg)")
    else:
        print(json.dumps(analyze_image(path), indent=2))
