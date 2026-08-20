#!/usr/bin/env bash
# Murang'a County — Video Processing Pipeline
# Transcodes raw Canon MXF/MP4 footage → web-friendly H.264 MP4,
# builds hero compilation + 8 sector tile snippets, deploys to R2.
#
# Usage:  sudo ./muranga-video-process.sh
#         (needs rclone or aws-cli configured for R2)

set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="/run/media/kicc/Extreme SSD/sorted/sorted/jjjj"
OUT="${ROOT}/storage/muranga-video"
R2_BUCKET="s3://kicc-media/muranga/video"
R2_ENDPOINT="https://c8416e05ed0a3554806be51aac862ec4.r2.cloudflarestorage.com"

mkdir -p "$OUT"/{hero,clips,sectors}

# ── 1. Transcode helper ──────────────────────────────────────────────────
transcode() {
    local input="$1" output="$2" duration="${3:-8}"
    if [[ -f "$output" ]]; then echo "  SKIP $output"; return; fi
    echo "  TRANSCODE $input → $output (${duration}s)"
    ffmpeg -y -i "$input" \
        -t "$duration" \
        -vf "scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(ow-iw)/2:(oh-ih)/2,fps=24" \
        -c:v libx264 -preset medium -crf 22 \
        -c:a aac -b:a 128k \
        -movflags +faststart \
        "$output" 2>/dev/null
}

# ── 2. Sector → location mapping ─────────────────────────────────────────
declare -A SECTOR_MAP=(
    [tourism]="kanunga falls,twin falls,gorges,rafting + resort"
    [agriculture]="tea landscape,purple tea,alba foods"
    [hospitality]="elipa,havila island"
    [culture]="mukurwe wa nyagathanga"
    [education]="muranga university,mukurwe wa nyagathanga primary school"
    [commerce]="guks"
    [transport]="drone/havila resort, island + rafting"
    [healthcare]="guks"
)

HERO_SOURCES=( \
    "drone/tea landscapes" "drone/havila resort, island + rafting" \
    "tea landscape" "kanunga falls" "twin falls" \
    "mukurwe wa nyagathanga" "rafting + resort/footage/raft + hotel" \
    "elipa/footage" "havila island/footage" "purple tea" \
)

# ── 3. Build hero compilation (12 clips × 4s = 48s) ──────────────────────
echo "=== BUILDING HERO COMPILATION ==="
HERO_CLIPS=()
for src in "${HERO_SOURCES[@]}"; do
    base="$(echo "$src" | tr '/' '_' | tr ' ' '_' | tr -d "'")"
    clip="$OUT/clips/hero_${base}.mp4"
    # Find first video file in source directory
    vid=$(find "$SRC/$src" -maxdepth 1 \( -name "*.MXF" -o -name "*.mp4" -o -name "*.MP4" \) 2>/dev/null | head -1)
    if [[ -z "$vid" ]]; then
        # Try subdirectories
        vid=$(find "$SRC/$src" -type f \( -name "*.MXF" -o -name "*.mp4" -o -name "*.MP4" \) 2>/dev/null | head -1)
    fi
    if [[ -n "$vid" ]]; then
        transcode "$vid" "$clip" 4
        HERO_CLIPS+=("$clip")
    else
        echo "  WARN: No video found in $src"
    fi
done

if [[ ${#HERO_CLIPS[@]} -ge 2 ]]; then
    # Build concat file
    concat="$OUT/hero_concat.txt"
    > "$concat"
    for f in "${HERO_CLIPS[@]}"; do
        echo "file '$f'" >> "$concat"
    done
    hero_out="$OUT/hero/muranga-hero-compilation.mp4"
    if [[ ! -f "$hero_out" ]]; then
        echo "=== CONCATENATING HERO VIDEO (${#HERO_CLIPS[@]} clips) ==="
        ffmpeg -y -f concat -safe 0 -i "$concat" \
            -c:v libx264 -preset medium -crf 20 \
            -c:a aac -b:a 128k \
            -movflags +faststart \
            "$hero_out" 2>/dev/null
        echo "  DONE: $hero_out"
    fi
else
    echo "ERROR: Not enough clips for hero video (need ≥2, got ${#HERO_CLIPS[@]})"
fi

# ── 4. Build sector snippet videos (10s each) ────────────────────────────
echo "=== BUILDING SECTOR SNIPPETS ==="
for sector in tourism agriculture hospitality culture education commerce transport healthcare; do
    snippet="$OUT/sectors/${sector}.mp4"
    [[ -f "$snippet" ]] && { echo "  SKIP $snippet"; continue; }

    # Get locations for this sector
    IFS=',' read -ra locs <<< "${SECTOR_MAP[$sector]}"
    sector_clips=()
    for loc in "${locs[@]}"; do
        lc="$(echo "$loc" | xargs)"
        base="$(echo "$lc" | tr '/' '_' | tr ' ' '_' | tr -d "'")"
        clip="$OUT/clips/sector_${sector}_${base}.mp4"
        if [[ ! -f "$clip" ]]; then
            vid=$(find "$SRC/$lc" -maxdepth 2 \( -name "*.MXF" -o -name "*.mp4" -o -name "*.MP4" \) 2>/dev/null | head -1)
            [[ -n "$vid" ]] && transcode "$vid" "$clip" 5
        fi
        [[ -f "$clip" ]] && sector_clips+=("$clip")
    done

    if [[ ${#sector_clips[@]} -ge 1 ]]; then
        sc="$OUT/sector_concat_${sector}.txt"
        > "$sc"
        for f in "${sector_clips[@]}"; do echo "file '$f'" >> "$sc"; done
        ffmpeg -y -f concat -safe 0 -i "$sc" \
            -c:v libx264 -preset medium -crf 20 \
            -c:a aac -b:a 128k \
            -movflags +faststart \
            "$snippet" 2>/dev/null
        echo "  DONE: $snippet"
    else
        echo "  WARN: No clips for sector '$sector'"
    fi
done

# ── 5. Upload to R2 ──────────────────────────────────────────────────────
echo "=== UPLOADING TO R2 ==="
if command -v rclone &>/dev/null; then
    rclone copy "$OUT/hero/" "r2:kicc-media/muranga/video/hero/" --progress 2>/dev/null || true
    rclone copy "$OUT/sectors/" "r2:kicc-media/muranga/video/sectors/" --progress 2>/dev/null || true
    echo "  R2 upload complete"
elif command -v aws &>/dev/null; then
    AWS_ACCESS_KEY_ID="${CLOUDFLARE_R2_ACCESS_KEY}" \
    AWS_SECRET_ACCESS_KEY="${CLOUDFLARE_R2_SECRET_KEY}" \
    aws s3 cp "$OUT/hero/" "$R2_BUCKET/hero/" --endpoint-url "$R2_ENDPOINT" --recursive 2>/dev/null || true
    AWS_ACCESS_KEY_ID="${CLOUDFLARE_R2_ACCESS_KEY}" \
    AWS_SECRET_ACCESS_KEY="${CLOUDFLARE_R2_SECRET_KEY}" \
    aws s3 cp "$OUT/sectors/" "$R2_BUCKET/sectors/" --endpoint-url "$R2_ENDPOINT" --recursive 2>/dev/null || true
    echo "  R2 upload complete"
else
    echo "  WARN: No rclone or aws-cli — upload manually:"
    echo "    rclone copy $OUT/hero/ r2:kicc-media/muranga/video/hero/"
    echo "    rclone copy $OUT/sectors/ r2:kicc-media/muranga/video/sectors/"
fi

echo "=== DONE ==="
echo "Hero:        ${hero_out:-N/A}"
echo "Sectors:     $(ls $OUT/sectors/ 2>/dev/null | wc -l) files"
echo "Total size:  $(du -sh "$OUT" 2>/dev/null | cut -f1)"