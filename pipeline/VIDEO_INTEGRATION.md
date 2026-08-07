# Video Engine Integration — KICC System Architecture

## The Problem

Right now we have:
- 47 county images + 29 sector images — **hardcoded paths**
- `generate_showcase.py` — fast 1080p video generator (~9s per video)
- `scene_composer.py` — 3D terrain HTML generator
- Screens at KICC that need **different-length videos** depending on placement

What we need:
> Upload images → tag by county + sector → system auto-generates videos at the right length for each screen

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    IMAGE REGISTRY (SQLite)                       │
│  Tracks all uploaded images with metadata:                      │
│  - path, county_id, sector_ids, tags, quality_score            │
│  - upload_date, source (admin upload / n8n auto-ingest)        │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                    VIDEO GENERATOR SERVICE                       │
│  Wraps generate_showcase.py with dynamic parameters:            │
│  - images: list of image paths (selected by screen context)    │
│  - duration: target length in seconds                          │
│  - fps, resolution, crf                                        │
│  - label style: county name, sector name, or none             │
│  Output: MP4 at pipeline/webgl_viewer/{job_id}.mp4            │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                    SCREEN CONTEXT API                            │
│  Maps physical screen → content parameters:                     │
│  Screen Location    │ Duration │ Content Source                 │
│  ─────────────────────────────────────────────────────         │
│  County Booth       │ 120-300s │ All images for that county    │
│  Sector Booth       │ 30-60s   │ Images tagged with that sector│
│  Hero Wall          │ 60-120s  │ Random from all counties      │
│  Info Kiosk         │ 15-30s   │ Latest 5 images (any county)  │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                    DISPLAY SCHEDULER                             │
│  Runs on each screen:                                           │
│  1. Boot → fetch screen config from API                        │
│  2. Request video generation if cached video is stale          │
│  3. Loop playlist (video + idle animation)                     │
│  4. Refresh every N hours                                      │
└─────────────────────────────────────────────────────────────────┘
```

## 1. Image Registry

A simple SQLite database (`data/image_registry.db`) that replaces the current hardcoded paths.

**Schema:**
```sql
CREATE TABLE images (
  id INTEGER PRIMARY KEY,
  filename TEXT NOT NULL,
  original_path TEXT NOT NULL,        -- upload source
  storage_path TEXT NOT NULL,          -- pipeline/webgl_viewer/images/{uuid}.jpg
  county_id TEXT,                      -- NULL if not county-specific
  sector_ids TEXT,                     -- comma-separated sector IDs
  tags TEXT,                           -- space-separated keywords
  quality_score REAL DEFAULT 0.5,      -- from image_analyzer
  scene_type TEXT,                     -- urban/coastal/rural/arid
  width INTEGER, height INTEGER,
  upload_date TEXT,
  source TEXT DEFAULT 'upload'        -- 'upload' | 'n8n' | 'batch'
);

CREATE TABLE screens (
  id TEXT PRIMARY KEY,                 -- e.g. 'county_booth_14'
  label TEXT,                          -- 'Mombasa County Booth'
  location TEXT,                       -- 'Hall A, Booth 3'
  county_id TEXT,                      -- target county or NULL
  sector_id TEXT,                      -- target sector or NULL
  target_duration_sec INTEGER,         -- desired video length
  refresh_interval_min INTEGER DEFAULT 60,
  active INTEGER DEFAULT 1
);
```

**Existing data migration** — the current 47 county photos + 29 sector photos get registered on first run:
```bash
python3 scripts/register_images.py
# Scans county profile pics labeled/ → registers as county images
# Scans m&k_labeled/Mombasa/ → registers as Mombasa county + sector
# Scans m&k_labeled/Kilifi/ → registers as Kilifi county + sector
```

## 2. Video Generator Service

A Python module (`pipeline/video_service.py`) that wraps `generate_showcase.py` with dynamic parameters.

```python
class VideoJob:
    screen_id: str
    duration_sec: int
    image_ids: list[int]
    fps: int = 30
    resolution: tuple = (1920, 1080)
    crf: int = 28
    status: str = "pending"  # pending/rendering/done/failed
    output_path: str = ""
    created_at: str
    completed_at: str = ""

def create_video(screen_id: str, duration_sec: int) -> VideoJob:
    """Main entry point: create a video for a screen."""
    # 1. Look up screen config
    screen = get_screen(screen_id)

    # 2. Select images based on screen context
    images = select_images(
        county_id=screen.county_id,
        sector_id=screen.sector_id,
        target_count=max(10, duration_sec // 4),  # ~4s per image
        randomize=True,
    )

    # 3. Generate video
    # Uses modified generate_showcase.py that accepts dynamic image list
    output = render_showcase(
        images=images,
        duration=duration_sec,
        label_style="county" if screen.county_id else "sector",
        output_name=f"auto_{screen_id}_{timestamp}.mp4",
    )

    # 4. Cache result
    return VideoJob(...)
```

**Image selection logic** — the core of the system:

```python
def select_images(
    county_id: str | None = None,
    sector_id: str | None = None,
    target_count: int = 15,
    randomize: bool = True,
    exclude_ids: list[int] | None = None,
) -> list[ImageRecord]:
    """
    Select images based on screen context:
    - If county_id + sector_id: images for that county's sector
    - If county_id only: all images for that county + county landmark
    - If sector_id only: images across all counties for that sector
    - If neither: random from all registered images
    
    Prioritization:
    1. Exact match (county + sector) goes first
    2. County landmark goes second
    3. Remaining slots filled randomly from pool
    """
```

## 3. Screen Context → Duration Mapping

How screen placement determines video parameters:

| Screen | Duration | Images | Style | Rationale |
|--------|----------|--------|-------|-----------|
| **County Main Booth** | 180-300s | 30-60 images from that county | Full showcase, county name labels | Visitors spend time here |
| **County Sub-booth** | 60-120s | 15-30 images from that county | Shorter loop, sector labels | Quick browse |
| **Sector Pavilion** | 30-60s | 8-15 images across counties for that sector | Sector-focused, no county labels | Cross-county comparison |
| **Hero Entrance Wall** | 60-120s | 15-30 random best images | Big production, title cards, music | First impression |
| **Info Kiosk** | 15-30s | 5-8 latest uploaded images | Clean, minimal, fast loop | Quick info |
| **Hallway Display** | 120-180s | 30-45 mixed images | Medium production, auto-advance | Walking speed viewing |

The logic is in `/home/kicc/Desktop/kicc/kenya-3d-platform/pipeline/video_service.py`:

```python
SCREEN_PRESETS = {
    "county_main":    {"duration": (180, 300), "image_count": (30, 60),  "label": "county"},
    "county_sub":     {"duration": (60, 120),  "image_count": (15, 30),  "label": "sector"},
    "sector_pavilion":{"duration": (30, 60),   "image_count": (8, 15),   "label": "sector"},
    "hero_wall":      {"duration": (60, 120),  "image_count": (15, 30),  "label": "title"},
    "info_kiosk":     {"duration": (15, 30),   "image_count": (5, 8),    "label": "none"},
    "hallway":        {"duration": (120, 180), "image_count": (30, 45),  "label": "county"},
}
```

## 4. Integration Points

### 4a. Laravel Backend (Admin Upload)
```php
// POST /api/video/generate
{
    "screen_id": "county_booth_14",
    "duration_sec": 240,
    "county_id": "mombasa"
}
// Response: { "job_id": "...", "estimated_time": 45, "status": "pending" }
```
The Laravel backend calls `python3 pipeline/video_service.py --job {job_id}` via Symfony Process.

### 4b. n8n Workflow (Auto-Ingest)
The existing `county-content-pipeline.json` n8n webhook can be extended:
1. Webhook receives new images + metadata (county, sector, tags)
2. n8n calls `python3 scripts/register_images.py --batch {payload}`
3. n8n triggers `python3 pipeline/video_service.py --screen {screen_id}`
4. New video appears on the screen's playlist automatically

### 4c. Display Script
Each screen runs a lightweight Python script (`scripts/screen_player.py`):
```bash
python3 scripts/screen_player.py --screen-id county_booth_14
```
It:
1. Fetches screen config from API (or local config file)
2. Checks if cached video exists and is fresh
3. If stale: requests new video generation (async), shows fallback loop
4. Once ready: plays video fullscreen on loop
5. Refreshes every N minutes

### 4d. CLI Tool
```bash
# Generate a video for any screen
python3 pipeline/video_service.py --screen county_booth_14

# Generate a one-off custom video
python3 pipeline/video_service.py \
  --images /path/to/img1.jpg /path/to/img2.jpg \
  --duration 120 \
  --label "My Exhibition" \
  --output my_video.mp4

# List all registered screens
python3 pipeline/video_service.py --list-screens

# Manually register images for a county
python3 scripts/register_images.py \
  --county turkana \
  --dir /path/to/turkana_photos/
```

## 5. What To Build (Priority Order)

### Phase 1 — Core Engine (can be done right now)
1. **`data/image_registry.db`** — SQLite database schema
2. **`scripts/register_images.py`** — Register existing images, tag by county/sector
3. **`pipeline/video_service.py`** — Dynamic video generator wrapping `generate_showcase.py`
4. **CLI integration** — `--screen` flag for `orchestrator_v2.py`

### Phase 2 — Display & Automation
5. **`scripts/screen_player.py`** — Screen playback script with auto-refresh
6. **Laravel API endpoints** — `/api/video/generate`, `/api/screens`, `/api/images`
7. **n8n workflow update** — Auto-trigger video re-generation on new images

### Phase 3 — Polish
8. **Dashboard** — Web UI showing all screens, their videos, generation status
9. **Randomization improvements** — Smart image selection (avoid repeats, ensure diversity)
10. **Analytics** — Track which videos are played most, screen engagement

## 6. File Changes Needed

### New files to create:
```
scripts/register_images.py     — Image registry management
pipeline/video_service.py      — Dynamic video generator
scripts/screen_player.py       — Screen playback
```

### Existing files to modify:
```
pipeline/orchestrator_v2.py    — Add --screen flag for auto video gen
scripts/generate_showcase.py   — Accept dynamic image lists (not just hardcoded dirs)
```

### Data files:
```
data/image_registry.db         — SQLite database
data/screens.json              — Screen config (seed data)
```

## 7. Existing Assets That Already Work

- **`generate_showcase.py`** — Fast video generation, 9s per 60s video at 1080p
- **`county_data.py`** — All 47 counties with sectors, regions
- **`data/sectors.json`** — 11 sectors with county mappings
- **`data/counties.json`** — Full county profiles with sector tags
- **`image_analyzer.py`** — Auto-tag images with scene type, quality score
- **`orchestrator_v2.py --showcase`** — Already generates showcase videos

The gap is: connecting uploaded images → dynamic video generation → screen playback.
