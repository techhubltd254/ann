"""Master pipeline orchestrator v2 — 47-County 3D Map Pipeline.

Integrated pipeline:
  1. ImageIngestor — walks county/sector photo dirs
  2. ImageAnalyzer — Pillow-based color/scene/quality analysis
  3. SpecGenerator — generates img2threejs JSON specs (for future 3D model gen)
  4. SceneComposer — generates interactive Three.js 47-county map
  5. FlowAdapter — generates promo videos via Veo API
  6. Report — pipeline summary

Runs standalone:  python3 orchestrator_v2.py
Runs as module:   from orchestrator_v2 import run_pipeline
"""

from __future__ import annotations

import json
import logging
import os
import sys
import time
from collections import Counter
from pathlib import Path
from typing import Any, Optional

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
)
logger = logging.getLogger("orchestrator_v2")

# Paths
PROJECT_ROOT = Path(__file__).resolve().parent.parent
COUNTY_DIR = Path("/home/kicc/Desktop/kicc/county profile pics labeled")
MOMBASA_DIR = Path("/home/kicc/Desktop/kicc/m&k_labeled/Mombasa")
KILIFI_DIR = Path("/home/kicc/Desktop/kicc/m&k_labeled/Kilifi")
OUTPUT_DIR = PROJECT_ROOT / "pipeline" / "webgl_viewer"
DATA_DIR = PROJECT_ROOT / "data"


class PipelineResult:
    """Structured pipeline result."""

    def __init__(self):
        self.status = "pending"
        self.counties_analyzed = 0
        self.sector_photos_analyzed = 0
        self.total_images = 0
        self.analysis_time_s = 0.0
        self.html_time_s = 0.0
        self.total_time_s = 0.0
        self.output_path: Optional[str] = None
        self.html_size_kb = 0.0
        self.scene_types: dict[str, int] = {}
        self.errors: list[str] = []
        self.billboards_generated = 0

    def to_dict(self) -> dict:
        return {
            "status": self.status,
            "counties": self.counties_analyzed,
            "sector_photos": self.sector_photos_analyzed,
            "total_images": self.total_images,
            "timing_s": {
                "analysis": round(self.analysis_time_s, 2),
                "html_generation": round(self.html_time_s, 2),
                "total": round(self.total_time_s, 2),
            },
            "output": self.output_path,
            "html_size_kb": round(self.html_size_kb, 0),
            "scene_types": self.scene_types,
            "billboards": self.billboards_generated,
            "errors": self.errors,
        }

    def __repr__(self) -> str:
        return (
            f"PipelineResult(status={self.status}, "
            f"counties={self.counties_analyzed}, "
            f"images={self.total_images}, "
            f"time={self.total_time_s:.1f}s)"
        )


def discover_images() -> dict[str, list[Path]]:
    """Walk all photo directories and return sorted file lists."""
    sources = {}
    if COUNTY_DIR.exists():
        sources["counties"] = sorted(COUNTY_DIR.glob("*"))
        logger.info(f"Discovered {len(sources['counties'])} county images")
    if MOMBASA_DIR.exists():
        sources["mombasa"] = sorted(MOMBASA_DIR.glob("*"))
        logger.info(f"Discovered {len(sources['mombasa'])} Mombasa sector images")
    if KILIFI_DIR.exists():
        sources["kilifi"] = sorted(KILIFI_DIR.glob("*"))
        logger.info(f"Discovered {len(sources['kilifi'])} Kilifi sector images")
    return sources


def analyze_images(
    sources: dict[str, list[Path]],
    skip_analysis: bool = False,
    cache: Optional[dict] = None,
) -> tuple[list[dict], dict[str, list[dict]], float]:
    """Analyze all discovered images.

    Returns: (county_analyses, sector_analyses, elapsed_seconds)
    """
    from image_analyzer import analyze_image

    county_analyses: list[dict] = []
    sector_analyses: dict[str, list[dict]] = {}
    t0 = time.time()

    # Check cache
    if cache and not skip_analysis:
        county_analyses = cache.get("county_analyses", [])
        sector_analyses = cache.get("sector_analyses", {})
        if county_analyses:
            logger.info(f"Using cached analysis ({len(county_analyses)} counties)")
            return county_analyses, sector_analyses, 0.0

    if skip_analysis:
        logger.info("Skip analysis requested, using empty analysis")
        return county_analyses, sector_analyses, 0.0

    for source_name, files in sources.items():
        is_county = source_name == "counties"
        label = "county" if is_county else f"{source_name} sector"
        logger.info(f"Analyzing {len(files)} {label} images...")

        results = []
        for f in files:
            try:
                result = analyze_image(str(f))
                results.append(result)
            except Exception as e:
                logger.error(f"Failed to analyze {f.name}: {e}")
                results.append({
                    "path": str(f),
                    "scene_type": "unknown",
                    "error": str(e),
                })

        if is_county:
            county_analyses = results
        else:
            sector_analyses[source_name] = results

    elapsed = time.time() - t0
    logger.info(f"Image analysis: {elapsed:.1f}s")
    return county_analyses, sector_analyses, elapsed


def generate_html(county_analyses: list[dict]) -> tuple[str, float]:
    """Generate the interactive 3D map HTML."""
    from scene_composer import compose_scene

    t0 = time.time()
    out_path = compose_scene(county_analyses, terrain=True)
    elapsed = time.time() - t0
    logger.info(f"HTML generation: {elapsed:.2f}s")
    return out_path, elapsed


def generate_promo_videos(
    county_analyses: list[dict],
    sector_analyses: dict[str, list[dict]],
    api_key: Optional[str] = None,
) -> list[str]:
    """Generate promo videos for highlighted counties via Veo/Flow adapter.

    Requires GOOGLE_AI_API_KEY or api_key param.
    Falls back silently if no key.
    """
    if not api_key:
        api_key = os.getenv("GOOGLE_AI_API_KEY")
    if not api_key:
        logger.info("No Google AI API key — skipping promo video generation")
        return []

    try:
        from flow_adapter import FlowAdapter
        flow = FlowAdapter(api_key=api_key)
        promos = []

        # Top 5 counties by sector diversity for promo
        counties_with_data = []
        for a in county_analyses:
            name = Path(a.get("path", "")).stem.replace("_", " ").replace(" City", "")
            from county_data import get_county_by_name
            c = get_county_by_name(name)
            if c:
                counties_with_data.append(c)

        top_counties = sorted(
            counties_with_data,
            key=lambda c: len(c.get("sectors", [])),
            reverse=True,
        )[:5]

        for c in top_counties:
            prompt = (
                f"KICC National Exhibition: {c['name']} County — "
                f"Explore {', '.join(c['sectors'][:3])} in {c['region']} Region. "
                f"Aerial drone shot, bright daylight, warm African tones, "
                f"National Geographic documentary aesthetic."
            )
            path = flow.generate_promo(
                prompt=prompt,
                output_path=str(OUTPUT_DIR / f"promo_{c['id']}.mp4"),
            )
            if path:
                promos.append(path)
                logger.info(f"Promo generated for {c['name']}")

        return promos

    except Exception as e:
        logger.warning(f"Promo generation failed: {e}")
        return []


def run_pipeline(
    skip_analysis: bool = False,
    force: bool = False,
    generate_promos: bool = False,
) -> PipelineResult:
    """Execute the full 47-county 3D map pipeline.

    Args:
        skip_analysis: Skip image analysis (use cache or defaults)
        force: Force re-analysis (ignore cache)
        generate_promos: Generate Veo promo videos (requires API key)

    Returns: PipelineResult with output path and stats.
    """
    result = PipelineResult()
    pipeline_start = time.time()

    logger.info("=" * 50)
    logger.info("KICC Pipeline v2 — 47-County 3D Map")
    logger.info("=" * 50)

    try:
        # Discover sources
        sources = discover_images()

        # Load cache
        cache = None
        cache_file = DATA_DIR / "county_analysis_cache.json"
        if cache_file.exists() and not force:
            try:
                cache = json.loads(cache_file.read_text())
            except Exception:
                pass

        # Analyze
        if skip_analysis:
            county_analyses, sector_analyses, analysis_time = analyze_images(
                sources, skip_analysis=True
            )
        else:
            county_analyses, sector_analyses, analysis_time = analyze_images(
                sources, cache=cache
            )
        result.analysis_time_s = analysis_time

        # Cache results
        if not skip_analysis and county_analyses:
            cache_data = {
                "county_analyses": county_analyses,
                "sector_analyses": sector_analyses,
                "count": len(county_analyses),
                "sectors": {k: len(v) for k, v in sector_analyses.items()},
            }
            DATA_DIR.mkdir(parents=True, exist_ok=True)
            cache_file.write_text(json.dumps(cache_data, indent=2))

        # Count
        result.counties_analyzed = len(county_analyses)
        result.sector_photos_analyzed = sum(len(v) for v in sector_analyses.values())
        result.total_images = result.counties_analyzed + result.sector_photos_analyzed

        # Track billboards (counties with photos)
        result.billboards_generated = result.counties_analyzed

        # Scene type distribution
        types = Counter(a.get("scene_type", "unknown") for a in county_analyses)
        result.scene_types = dict(types.most_common())

        # Generate HTML
        if county_analyses:
            out_path, html_time = generate_html(county_analyses)
            result.output_path = out_path
            result.html_time_s = html_time
            result.html_size_kb = os.path.getsize(out_path) / 1024

        # Generate promos
        if generate_promos:
            promo_paths = generate_promo_videos(county_analyses, sector_analyses)
            logger.info(f"Generated {len(promo_paths)} promo videos")

        result.total_time_s = time.time() - pipeline_start
        result.status = "complete"

    except Exception as e:
        result.status = "failed"
        result.errors.append(str(e))
        logger.error(f"Pipeline failed: {e}", exc_info=True)

    return result


def print_report(result: PipelineResult):
    """Print a formatted pipeline report."""
    d = result.to_dict()
    print()
    print("=" * 56)
    print("  KICC PIPELINE v2 — REPORT")
    print("=" * 56)
    print(f"  Status:              {d['status']}")
    print(f"  Counties:            {d['counties']}")
    print(f"  Sector photos:       {d['sector_photos']}")
    print(f"  Total images:        {d['total_images']}")
    print(f"  Billboard models:    {d['billboards']}")
    print(f"  HTML size:           {d['html_size_kb']:.0f} KB")
    print(f"  Analysis time:       {d['timing_s']['analysis']:.1f}s")
    print(f"  HTML generation:     {d['timing_s']['html_generation']:.2f}s")
    print(f"  Total time:          {d['timing_s']['total']:.1f}s")
    print(f"  Output:              {d['output']}")
    print()
    if d.get("scene_types"):
        print("  Scene type distribution:")
        for t, cnt in d["scene_types"].items():
            print(f"    {t:<12} {cnt:>2}")
    print()
    if d.get("errors"):
        print(f"  Errors ({len(d['errors'])}):")
        for e in d["errors"]:
            print(f"    - {e}")
    print("=" * 56)


if __name__ == "__main__":
    import argparse

    parser = argparse.ArgumentParser(description="KICC Pipeline v2 — 47-County 3D Map")
    parser.add_argument("--skip-analysis", action="store_true", help="Skip image analysis")
    parser.add_argument("--force", action="store_true", help="Force re-analysis")
    parser.add_argument("--promos", action="store_true", help="Generate Veo promo videos")
    parser.add_argument("--showcase", choices=["kilifi", "mombasa", "both"], default=None,
                        help="Generate showcase video for county/counties")
    args = parser.parse_args()

    if args.showcase:
        print("=" * 56)
        print("  KICC Showcase Video Generator")
        print("=" * 56)
        sys.path.insert(0, str(PROJECT_ROOT / "scripts"))
        from generate_showcase import generate_showcase, COUNTY_DIRS
        targets = ["kilifi", "mombasa"] if args.showcase == "both" else [args.showcase]
        for t in targets:
            generate_showcase(county_name=t.title(), county_dir=COUNTY_DIRS[t])
        sys.exit(0)

    result = run_pipeline(
        skip_analysis=args.skip_analysis,
        force=args.force,
        generate_promos=args.promos,
    )
    print_report(result)

    if result.status == "failed":
        sys.exit(1)
