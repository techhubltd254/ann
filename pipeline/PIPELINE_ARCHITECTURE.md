# KICC 3D Pipeline Architecture

## Overview

Two-tier pipeline: **Content Ingestion** (Google Flow → labeled images) feeds into **3D Rendering** (image→3D → Three.js scenes → AR/VR delivery).

```
┌─────────────────────────────────────────────────────────────────────┐
│                    CONTENT INGESTION TIER                            │
│                                                                     │
│  Google Flow / Veo ──→ Label Scripts ──→ Sorted Labeled Images      │
│  (47 counties +     (label_county_pics.py,   (county profile        │
│   Mombasa/Kilifi)    label_mk.py)            pics/, m&k_labeled/)   │
└──────────────────────────┬──────────────────────────────────────────┘
                           │ images
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      3D RENDERING TIER                               │
│                                                                     │
│  ┌──────────┐    ┌──────────────┐    ┌─────────────────────────┐   │
│  │img2threejs│───→│ Three.js     │───→│ WebGL Viewer + AR       │   │
│  │(image→3D) │    │Scene Builder │    │(browser deployment)     │   │
│  └──────────┘    └──────────────┘    └─────────────────────────┘   │
│       │                │                      │                     │
│       ▼                ▼                      ▼                     │
│  Procedural        Ocean, atmosphere,      Interactive              │
│  Three.js models   vegetation, etc.        county explorer          │
│  (GLB exportable)  (agent graphics)                               │
└─────────────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     DELIVERY TIER                                    │
│                                                                     │
│  ┌────────────┐    ┌──────────────┐    ┌───────────────────────┐   │
│  │ Web App    │    │ AR QuickLook│    │ Video Promo (Veo)    │   │
│  │ (desktop)  │    │ (mobile)    │    │ / DaVinci Resolve     │   │
│  └────────────┘    └──────────────┘    └───────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

## Repository Assets (in model_holders/)

| Repo | Stars | Purpose | How It Fits | Status |
|------|-------|---------|-------------|--------|
| `hoainho/img2threejs` | — | Takes reference image → procedural Three.js `THREE.Group` factory in TypeScript. Pure Python stdlib. | Tier 1: immediate county landmark → 3D model conversion | ✅ Downloaded, runs now |
| `scottstts/Threejs-Awesome-Graphics-Agent-Skills` | — | 20+ Three.js agent skills: ocean, atmosphere, procedural vegetation, architecture, shadows, post-processing. | Tier 2: enhance county scenes with environmental effects | ✅ Downloaded, needs npm install |
| `microsoft/TRELLIS.2` | 8.8k★ | 4B-param image→3D model using O-Voxel sparse voxel representation. Generates 512³–1536³ PBR textured assets in 3–60s on H100. Arbitrary topology (open surfaces, non-manifold). MIT license. Released Nov 2025. | Tier 3: premier image-to-3D for photo-realistic county landmark assets | ✅ Downloaded, needs NVIDIA GPU ≥24GB VRAM |
| `microsoft/TRELLIS` (v1) | 13.2k★ | 2B-param image/text→3D. Outputs Radiance Fields, 3D Gaussians, meshes. SLAT representation. MIT license. CVPR'25 Spotlight. | Tier 3 alternative: lighter (16GB VRAM), multi-format output | ✅ Downloaded, needs NVIDIA GPU ≥16GB VRAM |

## Pipeline Stages

### Stage 1: Image Ingestion (Done)
- Google Flow generates 47 county landmark images + 30 sector images
- `label_county_pics.py` overlays county names
- `label_mk.py` sorts Mombasa/Kilifi images into subfolders
- Output: Labeled images ready for 3D processing

### Stage 2: Image → 3D Model (New — uses img2threejs)
- Input: Labeled county landmark/sector images
- Process: Run `forge/stage1_intake/probe_image.py` → `stage2_spec/new_sculpt_spec.py` → `stage3_build/generate_threejs_factory.py`
- Output: Procedural Three.js models (TypeScript factory functions + optional GLB export)
- Each county landmark becomes an interactive 3D object

### Stage 3: Scene Composition (New — uses Three.js agent skills)
- Input: 3D models from Stage 2
- Process: Apply agent skills — `threejs-spectral-ocean` for coastal counties, `threejs-procedural-architecture` for buildings, `threejs-procedural-vegetation` for parks
- Output: Full 3D scenes per county with environmental context

### Stage 4: Pipeline Workers (Existing — adapted)
| Worker | Original Dep | New Approach |
|--------|-------------|--------------|
| `media_reader.py` | cv2, PIL, numpy, matplotlib, pytesseract | Pillow-only fallback (implemented) |
| `flow_adapter.py` | requests + Veo API | Keep as-is (API key required) |
| `orchestrator.py` | torch, opencv, etc. | Redesign as script runner: orchestrate img2threejs scripts + Three.js build |
| `depth_worker.py` | depth ML models | Deferred — requires GPU/ML runtime |
| `stereo_worker.py` | stereo conversion | Deferred — needs OpenCV |
| `splat_worker.py` | 3D Gaussian Splatting | Deferred — needs PyTorch |
| `refiner.py` | image enhancement | Deferred — needs ML |
| `resolver.py` | DaVinci Resolve API | Keep if Resolve is available locally |

### Stage 5: Delivery
- WebGL viewer serves interactive 3D county explorer (Three.js in browser)
- AR QuickLook for mobile (iOS/Android)
- Veo-generated promo videos via `flow_adapter.py`
- DaVinci color-graded videos via `resolver.py`

## Scripts to Create

### `scripts/generate_county_3d.py`
Orchestrates img2threejs pipeline for a single county image:
```python
# pseudocode:
# 1. probe_image.py <county_image>
# 2. new_pre_spec_assessment.py "CountyName" --image <image> --out assessment.json
# 3. new_sculpt_spec.py "CountyName" --image <image> --assessment assessment.json --out spec.json
# 4. validate_sculpt_spec.py spec.json --strict-quality
# 5. generate_threejs_factory.py spec.json --out src/models/CountyName.ts
```

### `scripts/batch_county_3d.py`
Runs generate_county_3d.py for all 47 counties.

### `scripts/compose_county_scene.py`
Composes a full Three.js scene with environment (terrain, sky, vegetation) for a county using the agent skills.

## System Constraints

- **No pip packages** — Python 3.14.4 stdlib + Pillow only
- **No git** — curl/wget for downloads
- **No GPU/ML** — torch/opencv not available
- **Node may be available** for Three.js build tooling
- **img2threejs runs on pure Python** — zero deps
- **Three.js agent skills** — installed via `npx` as agent skills (npm)

## Long-term Vision

```
┌──────────┐   ┌───────────┐   ┌──────────┐   ┌─────────────┐
│  County   │   │img2threejs│   │ Three.js │   │  WebGL/AR   │
│  Photos   │──→│  (agent)  │──→│  Skills  │──→│  Delivery   │
└──────────┘   └───────────┘   └──────────┘   └─────────────┘
                                                   │
                                          ┌────────┴────────┐
                                          │  DaVinci Resolve │
                                          │  (color grade)   │
                                          └─────────────────┘
                                                   │
                                          ┌────────┴────────┐
                                          │  Google Veo     │
                                          │  (promo vids)   │
                                          └─────────────────┘
```

## Tiered Integration Strategy (Monster Pipeline)

```
Tier 1 [NOW]     img2threejs (pure Python, zero-deps)
  └─→ County landmark photos → procedural Three.js models
  └─→ Built into kenya_3d_map.html via billboard approach

Tier 2 [NEEDS npm]     Threejs-Awesome-Graphics-Agent-Skills
  └─→ Ocean, atmosphere, vegetation, architecture effects
  └─→ Run agents via npx against county 3D scenes

Tier 3 [NEEDS GPU]     microsoft/TRELLIS.2 (4B, O-Voxel)
  └─→ Image → PBR textured 3D mesh in seconds
  └─→ Up to 1536³ resolution, arbitrary topology
  └─→ Requires NVIDIA GPU ≥24GB VRAM (H100 ideal)
  └─→ Apple Silicon port available (slower, 3.5 min on M4 Pro)

Tier 4 [NEEDS GPU]     microsoft/TRELLIS v1 (2B, SLAT)
  └─→ Image/text → Radiance Fields / 3D Gaussians / meshes
  └─→ Requires NVIDIA GPU ≥16GB VRAM
  └─→ Lighter alternative to TRELLIS.2

Tier 5 [FUTURE]     facebookresearch/MapAnything (404/dead), ShapeR, gsplat
  └─→ MapAnything: claimed single-image city-scale 3D, never published
  └─→ ShapeR: metric reconstruction from Aria glasses (CUDA 12.8+torchsparse)
  └─→ NerfStudio/gsplat: 3D Gaussian Splatting from video
```

When GPU-capable hardware becomes available, the highest-impact addition is **TRELLIS.2** (4B parameters, O-Voxel native sparse voxels, full PBR materials, arbitrary topology). It would replace the billboard approach with true 3D geometry for each county landmark. The existing pipeline already generates the correct directory structure for integration — just swap the image path for a `.glb` path and update the Three.js loader.

### Repositories to monitor
- `shivampkumar/trellis-mac` — Apple Silicon port of TRELLIS.2 for Mac-based deployment
- `huggingface/microsoft/TRELLIS.2` — pretrained checkpoint weights (4B params)
- Hugging Face Spaces demo: `microsoft/TRELLIS.2` for quick experimentation
