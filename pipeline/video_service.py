"""Video Service — dynamic video generation from the Image Registry.

Queries registered images by county/sector, selects randomly, and generates
showcase videos at the desired duration. Designed for automated screen playback.

Usage:
  python3 pipeline/video_service.py for-screen county_main_14
  python3 pipeline/video_service.py custom --county mombasa --duration 120
  python3 pipeline/video_service.py custom --sector tourism --duration 60
  python3 pipeline/video_service.py custom --images img1.jpg img2.jpg
"""

from __future__ import annotations

import argparse
import json
import random
import sqlite3
import subprocess
import sys
import time
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parent.parent
SCRIPTS_DIR = PROJECT_ROOT / "scripts"
OUTPUT_DIR = PROJECT_ROOT / "pipeline" / "webgl_viewer"
DB_PATH = PROJECT_ROOT / "data" / "image_registry.db"

sys.path.insert(0, str(SCRIPTS_DIR))
# We import generate_showcase directly when needed

# ── Screen presets ──
SCREEN_PRESETS = {
    "county_main":    {"dur_range": (180, 300), "img_range": (30, 60),  "clip_sec": 6, "label": "county",  "title": True},
    "county_sub":     {"dur_range": (60, 120),  "img_range": (15, 30),  "clip_sec": 5, "label": "sector",  "title": True},
    "sector_pavilion":{"dur_range": (30, 60),   "img_range": (8, 15),   "clip_sec": 4, "label": "sector",  "title": False},
    "hero_wall":      {"dur_range": (60, 120),  "img_range": (15, 30),  "clip_sec": 5, "label": "auto",    "title": True},
    "info_kiosk":     {"dur_range": (15, 30),   "img_range": (5, 8),    "clip_sec": 3, "label": "none",    "title": False},
    "hallway":        {"dur_range": (120, 180), "img_range": (30, 45),  "clip_sec": 5, "label": "county",  "title": True},
}


def get_db() -> sqlite3.Connection:
    DB_PATH.parent.mkdir(parents=True, exist_ok=True)
    conn = sqlite3.connect(str(DB_PATH))
    conn.row_factory = sqlite3.Row
    return conn


def select_images(
    county_id: str | None = None,
    sector_id: str | None = None,
    target_count: int = 15,
    exclude_ids: list[int] | None = None,
) -> list[dict]:
    """Select images from the registry matching the query.

    Priority: exact match (county + sector) → county-only → sector-only → random.
    """
    conn = get_db()
    exclude = exclude_ids or []

    conditions = []
    params = []

    # Build the WHERE clause for candidate images
    if county_id and sector_id:
        # Prefer images matching BOTH county AND sector
        conditions.append("county_id = ? AND instr(sector_ids, ?) > 0")
        params.extend([county_id, sector_id])
    elif county_id:
        conditions.append("county_id = ?")
        params.append(county_id)
    elif sector_id:
        conditions.append("instr(sector_ids, ?) > 0")
        params.append(sector_id)

    where = " AND ".join(conditions) if conditions else "1=1"
    if exclude:
        where += f" AND id NOT IN ({','.join('?' * len(exclude))})"
        params.extend(exclude)

    rows = conn.execute(
        f"SELECT * FROM images WHERE {where} ORDER BY quality_score DESC",
        params,
    ).fetchall()
    conn.close()

    if not rows:
        return []

    # Select unique images, ordered by quality, then shuffle top candidates
    unique = list({r["id"]: dict(r) for r in rows}.values())
    unique.sort(key=lambda x: x["quality_score"], reverse=True)

    # Pick from top tier, shuffle for variety
    pool = unique[:max(target_count * 2, 20)] if len(unique) > target_count else unique
    selected = random.sample(pool, min(target_count, len(pool)))
    random.shuffle(selected)
    return selected


def get_screen_config(screen_id: str) -> dict | None:
    """Get screen configuration from the registry."""
    conn = get_db()
    row = conn.execute("SELECT * FROM screens WHERE id = ?", (screen_id,)).fetchone()
    conn.close()
    return dict(row) if row else None


def calc_clip_params(
    image_count: int,
    target_duration: int,
    preset: dict,
) -> tuple[float, float]:
    """Calculate per-clip duration and title card duration.

    Returns: (clip_duration, title_duration)
    """
    title_dur = 3.0 if preset["title"] else 0.0
    avail = target_duration - title_dur
    if image_count <= 0:
        return (preset["clip_sec"], title_dur)
    clip_dur = max(2.0, min(preset["clip_sec"] + 1, avail / image_count))
    return (clip_dur, title_dur)


def generate_for_screen(screen_id: str, output_name: str | None = None) -> str:
    """Generate a video for a registered screen."""
    screen = get_screen_config(screen_id)
    if not screen:
        print(f"  Screen '{screen_id}' not found in registry")
        print("  Run: python3 scripts/register_images.py init")
        sys.exit(1)

    # Determine preset from screen_id prefix
    prefix = screen_id.rsplit("_", 1)[0] if "_" in screen_id else screen_id
    preset = SCREEN_PRESETS.get(prefix, SCREEN_PRESETS["county_sub"])

    target_dur = screen["target_duration_sec"]
    min_img = screen["min_images"] or preset["img_range"][0]
    max_img = screen["max_images"] or preset["img_range"][1]
    target_count = min(max_img, max(min_img, target_dur // 4))

    print(f"  Screen:  {screen['label']} ({screen_id})")
    print(f"  Target:  {target_dur}s, ~{target_count} images")

    images = select_images(
        county_id=screen["county_id"],
        sector_id=screen["sector_id"],
        target_count=target_count,
    )

    if not images:
        print(f"  No images found for screen '{screen_id}'")
        sys.exit(1)

    clip_dur, title_dur = calc_clip_params(len(images), target_dur, preset)
    county_name = screen["county_id"].title() if screen["county_id"] else "Kenya"

    if not output_name:
        output_name = f"auto_{screen_id}.mp4"

    # Call generate_showcase
    from generate_showcase import generate_showcase

    result = generate_showcase(
        county_name=county_name,
        image_paths=[img["storage_path"] for img in images],
        clip_duration=clip_dur,
        label_style=preset["label"],
        show_title_card=preset["title"],
        output_name=output_name,
    )

    return str(result)


def generate_custom(
    county_id: str | None = None,
    sector_id: str | None = None,
    image_paths: list[str] | None = None,
    duration: int = 60,
    label_style: str = "auto",
    output_name: str | None = None,
    title: bool = True,
) -> str:
    """Generate a custom video with specific parameters."""
    if image_paths:
        # Use explicitly provided images
        images = [{"storage_path": p} for p in image_paths]
        county_name = "Custom"
    else:
        images = select_images(county_id=county_id, sector_id=sector_id, target_count=duration // 4)
        county_name = county_id.title() if county_id else (sector_id.title() if sector_id else "Kenya")

    if not images:
        print("  No images found matching the query")
        sys.exit(1)

    clip_dur = max(3.0, duration / len(images))
    if not output_name:
        parts = [county_name.lower().replace(" ", "_"), str(duration), "s"]
        output_name = "_".join(parts) + ".mp4"

    from generate_showcase import generate_showcase

    result = generate_showcase(
        county_name=county_name,
        image_paths=[img["storage_path"] for img in images],
        clip_duration=clip_dur,
        label_style=label_style,
        show_title_card=title,
        output_name=output_name,
    )
    return str(result)


def list_screens_with_images():
    """List all screens and their available image counts."""
    conn = get_db()
    rows = conn.execute("""
        SELECT s.*, COUNT(i.id) as image_count
        FROM screens s
        LEFT JOIN images i ON
            (s.county_id IS NULL OR i.county_id = s.county_id)
            AND (s.sector_id IS NULL OR instr(i.sector_ids, s.sector_id) > 0)
        GROUP BY s.id
        ORDER BY s.id
    """).fetchall()
    conn.close()
    if not rows:
        print("  No screens configured. Run: python3 scripts/register_images.py init")
        return
    for r in rows:
        flag = "✓" if r["image_count"] >= (r["min_images"] or 10) else "✗"
        print(f"  {flag} {r['id'][:30]:30s} "
              f"dur={r['target_duration_sec']:3d}s "
              f"imgs={r['image_count']:2d}/{r['min_images'] or 10}")


if __name__ == "__main__":
    p = argparse.ArgumentParser(description="KICC Video Service")
    sub = p.add_subparsers(dest="cmd", required=True)

    # for-screen
    fs = sub.add_parser("for-screen", help="Generate video for a registered screen")
    fs.add_argument("screen_id", help="Screen ID from registry")

    # custom
    cu = sub.add_parser("custom", help="Generate custom video")
    cu.add_argument("--county", help="County ID filter")
    cu.add_argument("--sector", help="Sector ID filter")
    cu.add_argument("--images", nargs="+", help="Explicit image paths")
    cu.add_argument("--duration", type=int, default=60, help="Target duration in seconds")
    cu.add_argument("--label", choices=["auto", "county", "sector", "none"], default="auto")
    cu.add_argument("--no-title", action="store_false", dest="title", default=True)
    cu.add_argument("--output", help="Output filename")

    # list
    sub.add_parser("list-screens", help="List screens with image counts")

    args = p.parse_args()

    print("=" * 52)
    print("  KICC Video Service")
    print("=" * 52)
    t0 = time.time()

    if args.cmd == "for-screen":
        out = generate_for_screen(args.screen_id)
    elif args.cmd == "custom":
        out = generate_custom(
            county_id=args.county,
            sector_id=args.sector,
            image_paths=args.images,
            duration=args.duration,
            label_style=args.label,
            output_name=args.output,
            title=args.title,
        )
    elif args.cmd == "list-screens":
        list_screens_with_images()
        sys.exit(0)

    elapsed = time.time() - t0
    print(f"\n  Total: {elapsed:.1f}s")
    print(f"  Video: {out}")
