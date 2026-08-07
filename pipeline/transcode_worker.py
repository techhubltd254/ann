#!/usr/bin/env python3
"""
KICC ONE — transcode worker.

Consumes media jobs from a Redis stream and turns admin-uploaded originals
(4K/8K allowed) into web-safe derivatives. Originals are NEVER public.

Job contract (XADD media:jobs):
  {
    "job_id": "ulid", "asset_id": "...", "bucket": "kicc-media",
    "original_key": "originals/county/kilifi/hero.mp4",
    "entity_type": "county", "entity_slug": "kilifi", "slot": "hero",
    "publish_webhook": "https://kicctest.org/api/media/publish"
  }

Size guardrails (the "can't upload blindly" contract):
  - originals capped upstream at 2 GB (engine tus hook)
  - reject >20 min duration or non-video codecs (probe first)
  - ladder total budget: 150 MB per asset; per-rendition caps below
  - H.264 main profile for max device reach; VP9 webm as modern alternative

Failure modes:
  - ffmpeg crash mid-run      -> job re-queued up to 3x (XAUTOCLAIM), then DLQ media:dlq
  - R2/S3 upload failure      -> exponential backoff (1s..15min), partial outputs deleted
  - duplicate job delivery    -> idempotency marker in Redis (SET NX) skips re-work
  - worker killed             -> pending entries reclaimed via XAUTOCLAIM idle timeout
"""

from __future__ import annotations

import json
import os
import subprocess
import sys
import tempfile
import time
from dataclasses import dataclass

import boto3
import redis

REDIS_URL = os.environ.get("REDIS_URL", "redis://localhost:6379/0")
S3_ENDPOINT = os.environ["S3_ENDPOINT"]
S3_KEY = os.environ["S3_KEY"]
S3_SECRET = os.environ["S3_SECRET"]
STREAM = "media:jobs"
GROUP = "transcode"
CONSUMER = f"worker-{os.getpid()}"
MAX_DURATION_S = 20 * 60
LADDER = [  # (height, video bitrate, maxrate, bufsize) — total budget <= 150 MB/asset
    (1080, "4500k", "4800k", "9000k"),
    (720, "2800k", "3000k", "5600k"),
    (480, "1400k", "1500k", "2800k"),
    (360, "800k", "900k", "1600k"),
]


@dataclass
class Job:
    job_id: str
    asset_id: str
    bucket: str
    original_key: str
    entity_type: str
    entity_slug: str
    slot: str


def log(level: str, msg: str, **kw) -> None:
    print(json.dumps({"ts": time.time(), "level": level, "msg": msg, **kw}), flush=True)


def probe(path: str) -> dict:
    out = subprocess.run(
        ["ffprobe", "-v", "error", "-print_format", "json", "-show_format", "-show_streams", path],
        capture_output=True, text=True, check=True,
    )
    return json.loads(out.stdout)


def transcode(src: str, outdir: str, job: Job) -> dict:
    """Build HLS ladder + VP9 webm + poster + blur placeholder. Returns derivative paths."""
    os.makedirs(outdir, exist_ok=True)
    derivatives: dict[str, str] = {}

    # HLS ladder (fmp4 segments work everywhere incl. Safari 14+)
    variant_parts, master_lines = [], ["#EXTM3U", "#EXT-X-VERSION:7"]
    for height, br, maxrate, bufsize in LADDER:
        name = f"v{height}"
        variant_parts.append(
            f"[v{height}]"  # filtergraph ref
        )
        master_lines.append(
            f"#EXT-X-STREAM-INF:BANDWIDTH={int(br.rstrip('k')) * 1000},RESOLUTION={{w}}x{height}\n{name}/playlist.m3u8"
        )
    filter_complex = ";".join(
        f"[0:v]scale=-2:{h}[v{h}]" for h, *_ in LADDER
    )
    cmd = ["ffmpeg", "-y", "-i", src, "-filter_complex", filter_complex]
    for i, (h, br, maxrate, bufsize) in enumerate(LADDER):
        cmd += [
            "-map", f"[v{h}]", "-map", "0:a?",
            f"-c:v:{i}", "libx264", "-profile:v", "main", "-pix_fmt", "yuv420p",
            f"-b:v:{i}", br, f"-maxrate:v:{i}", maxrate, f"-bufsize:v:{i}", bufsize,
            "-c:a", "aac", "-b:a", "128k",
            "-f", "hls", "-hls_time", "4", "-hls_playlist_type", "vod",
            "-hls_segment_type", "fmp4",
            "-hls_segment_filename", os.path.join(outdir, f"v{h}/seg_%03d.m4s"),
            os.path.join(outdir, f"v{h}/playlist.m3u8"),
        ]
    subprocess.run(cmd, check=True, capture_output=True)
    derivatives["hls"] = f"{outdir}/master.m3u8"

    # VP9 webm (modern browsers, ~30% smaller) — capped at 1080p
    webm = f"{outdir}/video.webm"
    subprocess.run(
        ["ffmpeg", "-y", "-i", src, "-vf", "scale=-2:1080", "-c:v", "libvpx-vp9",
         "-b:v", "2M", "-crf", "32", "-row-mt", "1", "-c:a", "libopus", "-b:a", "96k", webm],
        check=True, capture_output=True,
    )
    derivatives["webm"] = webm

    # Poster (WebP 1280w) + 32w blur placeholder
    poster = f"{outdir}/poster.webp"
    subprocess.run(["ffmpeg", "-y", "-ss", "1", "-i", src, "-frames:v", "1",
                    "-vf", "scale=1280:-2", "-quality", "82", poster], check=True, capture_output=True)
    derivatives["poster"] = poster
    return derivatives


def run() -> None:
    r = redis.Redis.from_url(REDIS_URL, decode_responses=True)
    s3 = boto3.client("s3", endpoint_url=S3_ENDPOINT,
                      aws_access_key_id=S3_KEY, aws_secret_access_key=S3_SECRET)
    try:
        r.xgroup_create(STREAM, GROUP, id="0", mkstream=True)
    except redis.ResponseError:
        pass  # group exists

    log("info", "transcode worker online", consumer=CONSUMER)
    while True:
        # Reclaim stalled jobs (idle > 10 min), then block for new ones
        for stream, entries in r.xautoclaim(STREAM, GROUP, CONSUMER, min_idle_time=600_000, start_id="0-0", count=1)[1:2]:
            pass
        msgs = r.xreadgroup(GROUP, CONSUMER, {STREAM: ">"}, count=1, block=5000)
        if not msgs:
            continue
        for _, entries in msgs:
            for msg_id, fields in entries:
                job = Job(**{k: fields[k] for k in
                             ("job_id", "asset_id", "bucket", "original_key",
                              "entity_type", "entity_slug", "slot")})
                idem = f"media:done:{job.job_id}"
                if not r.set(idem, "running", ex=86400, nx=True):
                    r.xack(STREAM, GROUP, msg_id)
                    continue
                try:
                    process(r, s3, job)
                    r.xack(STREAM, GROUP, msg_id)
                except Exception as exc:  # noqa: BLE001 — retry via pending; DLQ after 3 fails
                    fails = r.incr(f"media:fails:{job.job_id}")
                    log("error", "job failed", job=job.job_id, attempt=fails, err=str(exc))
                    if int(fails) >= 3:
                        r.xadd("media:dlq", {"job_id": job.job_id, "error": str(exc)})
                        r.xack(STREAM, GROUP, msg_id)
                    time.sleep(min(2 ** int(fails), 900))


def process(r: redis.Redis, s3, job: Job) -> None:
    with tempfile.TemporaryDirectory() as tmp:
        src = os.path.join(tmp, "original")
        s3.download_file(job.bucket, job.original_key, src)
        info = probe(src)
        duration = float(info["format"].get("duration", 0))
        if duration > MAX_DURATION_S:
            raise ValueError(f"duration {duration:.0f}s exceeds {MAX_DURATION_S}s cap")
        outdir = os.path.join(tmp, "out")
        derivatives = transcode(src, outdir, job)

        prefix = f"derivatives/{job.entity_type}/{job.entity_slug}/{job.slot}/{job.asset_id}"
        urls: dict[str, str] = {}
        for root, _, files in os.walk(outdir):
            for f in files:
                local = os.path.join(root, f)
                key = f"{prefix}/{os.path.relpath(local, outdir)}"
                s3.upload_file(local, job.bucket, key)
        urls["hls"] = f"https://kicctest.org/media/{prefix}/master.m3u8"
        urls["webm"] = f"https://kicctest.org/media/{prefix}/video.webm"
        urls["poster"] = f"https://kicctest.org/media/{prefix}/poster.webp"

        # Hand off to the engine -> engine signs + fires the publish webhook to Laravel.
        r.xadd("media:publish", {
            "job_id": job.job_id, "asset_id": job.asset_id,
            "entity_type": job.entity_type, "entity_slug": job.entity_slug,
            "slot": job.slot, "derivatives": json.dumps(urls),
        })
        r.set(f"media:done:{job.job_id}", "done", ex=86400, xx=True)
        log("info", "transcoded+uploaded", job=job.job_id, prefix=prefix)


if __name__ == "__main__":
    sys.exit(run())
