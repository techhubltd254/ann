# KICC 3D Media Pipeline

One folder of photos → holo 3D videos. Runs on GPU (Vast.ai/CUDA) or CPU.

## Components
| Script | What it does |
|---|---|
| `holo_convert.py` | single image → depth map (Depth-Anything V2) + wiggle-gram video |
| `cinematic.py` | image + depth → 3D camera-move (dolly/pan) cinematic loop |
| `run_pipeline.py` | folder of photos → auto-detects multi-angle bursts → real 3D videos |
| `transcode_worker.py` | Redis-queue video transcoding (HLS ladder) for uploads |

## Run locally (CPU works, GPU if present)
```bash
python3 -m venv .venv && .venv/bin/pip install torch torchvision transformers opencv-python-headless pillow numpy
.venv/bin/python pipeline/run_pipeline.py /path/to/photos --burst
```

## Run on Vast.ai GPU (fast, for big batches)
> NOTE: this network's ISP blocks vast.ai proxy ports — use a VPN/hosted runner if SSH fails.
```bash
# 1. rent an instance (pytorch image), get ssh host:port from the API
# 2. push the pipeline + photos
scp -P <port> -r pipeline root@<host>:/workspace/
# 3. on the instance
python pipeline/run_pipeline.py /workspace/photos --burst --upload
```

## Conventions (where outputs land)
- Per subject: `<name>/wiggle.mp4`, `cinematic.mp4`, `depth.png`, `source.jpg`
- R2 (site): `derivatives/holo/{county}-{sector}-{id}/wiggle.mp4`
- Local archive: `kicc-one/data/holographic/`

## Rules
- Originals never reach browsers — only transcoded derivatives (web-sized, faststart).
- Posters are frames from the same footage (image matches video exactly).
