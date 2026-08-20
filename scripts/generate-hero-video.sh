#!/usr/bin/env bash
# KICC Cinematic Welcome Video Generator
set -euo pipefail

SRC="/home/kicc/Desktop/kicc/kicc-platform/kicc building"
OUT="/tmp/kicc-hero-video"
FINAL="/tmp/kicc-hero-final.mp4"
mkdir -p "$OUT/segments" "$OUT/resized"

echo "=== Resizing images to 1920x1080 ==="
# Act 1: Arrival & Grand Exterior
for f in "KICC Main Gate.jpg.jpeg" "Comesa FrontSide.jpeg"; do
  ffmpeg -y -i "$SRC/$f" -vf "scale=1920:1080:force_original_aspect_ratio=increase,crop=1920:1080" -quality 95 "$OUT/resized/$(basename $f .jpeg).jpg" 2>/dev/null &
done

# Act 2: Grand Concourse
for f in "Tsavo Staircase.jpg.jpeg" "Closer to Tsavo-from reception.jpg.jpeg" "LenanaEntrance-Stitch.jpeg" "Few steps heading to reception.jpeg"; do
  ffmpeg -y -i "$SRC/$f" -vf "scale=1920:1080:force_original_aspect_ratio=increase,crop=1920:1080" -quality 95 "$OUT/resized/$(basename $f .jpeg).jpg" 2>/dev/null &
done

# Act 3: Plenary Halls
for f in "LenanaRoom-Stitch.jpeg" "Inside Tsavo 5.jpg.jpeg" "Amphitheater Stitch" "Inside Tsavo 6.jpg.jpeg"; do
  if [ -f "$SRC/$f" ]; then
    ffmpeg -y -i "$SRC/$f" -vf "scale=1920:1080:force_original_aspect_ratio=increase,crop=1920:1080" -quality 95 "$OUT/resized/$(basename $f .jpeg).jpg" 2>/dev/null &
  fi
done

# Act 4: Outro
for f in "Tsavo Barricade Stitch.jpeg" "MainFront-Stitch.jpeg"; do
  ffmpeg -y -i "$SRC/$f" -vf "scale=1920:1080:force_original_aspect_ratio=increase,crop=1920:1080" -quality 95 "$OUT/resized/$(basename $f .jpeg).jpg" 2>/dev/null &
done
wait
echo "All images resized"

echo "=== Creating animated scenes ==="
FPS=30
DUR=6

# Scene 1: KICC Main Gate — upward crane shot (0:00-0:06)
ffmpeg -y -loop 1 -i "$OUT/resized/KICC Main Gate.jpg.jpg" \
  -vf "zoompan=z='if(lte(zoom,1.0),1.3,zoom+0.01)':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)-100':d=180:fps=$FPS, \
       drawtext=text='LIVE':fontcolor=red:fontsize=36:x=20:y=20:box=1:boxcolor=black@0.7:boxborderw=8, \
       drawtext=text='NAIROBI, KENYA':fontcolor=white:fontsize=24:x=95:y=28:box=1:boxcolor=black@0.7:boxborderw=6, \
       drawtext=text='KICC — East Africa'\''s Premier Convention Hub':fontcolor=white:fontsize=22:x=20:y=h-120:box=1:boxcolor=black@0.6:boxborderw=10, \
       drawtext=text='INVESTMENT SUMMIT 2026  •  TRADE FAIR  •  STATE FUNCTIONS':fontcolor=white:fontsize=14:x=20:y=h-40:box=1:boxcolor=black@0.5:boxborderw=6" \
  -c:v libx264 -t $DUR -pix_fmt yuv420p -preset medium -crf 20 "$OUT/segments/scene1.mp4" 2>/dev/null

# Scene 2: Comesa FrontSide — sweeping wide tracking shot (0:06-0:12)
ffmpeg -y -loop 1 -i "$OUT/resized/Comesa FrontSide.jpg" \
  -vf "zoompan=z='if(lte(zoom,1.0),1.1,zoom+0.005)':x='iw/2-(iw/zoom/2)+100':y='ih/2-(ih/zoom/2)':d=180:fps=$FPS, \
       drawtext=text='COMESA GROUNDS: EXPANSIVE OUTDOOR EXPO & EXHIBITION ARENA':fontcolor=white:fontsize=20:x=20:y=h-80:box=1:boxcolor=black@0.6:boxborderw=8" \
  -c:v libx264 -t $DUR -pix_fmt yuv420p -preset medium -crf 20 "$OUT/segments/scene2.mp4" 2>/dev/null

# Scene 3: Tsavo Staircase — push-in tracking up stairs (0:12-0:18)
ffmpeg -y -loop 1 -i "$OUT/resized/Tsavo Staircase.jpg.jpg" \
  -vf "zoompan=z='if(lte(zoom,1.0),1.2,zoom+0.015)':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)-50':d=180:fps=$FPS, \
       drawtext=text='MAIN CONCOURSE: MULTI-TIER ACCESS LEADING TO PRIMARY PLENARY HALLS':fontcolor=white:fontsize=20:x=20:y=h-80:box=1:boxcolor=black@0.6:boxborderw=8" \
  -c:v libx264 -t $DUR -pix_fmt yuv420p -preset medium -crf 20 "$OUT/segments/scene3.mp4" 2>/dev/null

# Scene 4: Lenana Entrance — corridor dolly (0:18-0:24)
ffmpeg -y -loop 1 -i "$OUT/resized/LenanaEntrance-Stitch.jpg" \
  -vf "zoompan=z='if(lte(zoom,1.0),1.0,zoom+0.008)':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d=180:fps=$FPS, \
       drawtext=text='EXECUTIVE WING: DIRECT DELEGATE ACCESS TO ABERDARE & LENANA HALLS':fontcolor=white:fontsize=20:x=20:y=h-80:box=1:boxcolor=black@0.6:boxborderw=8" \
  -c:v libx264 -t $DUR -pix_fmt yuv420p -preset medium -crf 20 "$OUT/segments/scene4.mp4" 2>/dev/null

# Scene 5: Lenana Room — 180° panoramic reveal (0:24-0:30)
ffmpeg -y -loop 1 -i "$OUT/resized/LenanaRoom-Stitch.jpg" \
  -vf "zoompan=z='if(lte(zoom,1.0),1.05,zoom+0.003)':x='iw/2-(iw/zoom/2)-100':y='ih/2-(ih/zoom/2)':d=180:fps=$FPS, \
       drawtext=text='LENANA PLENARY: FULLY EQUIPPED HYBRID & DIPLOMATIC ASSEMBLY SUITE':fontcolor=white:fontsize=20:x=20:y=h-80:box=1:boxcolor=black@0.6:boxborderw=8, \
       drawtext=text='SIMULTANEOUS TRANSLATION • PRESS GALLERY • 4K HYBRID STREAMING':fontcolor=white:fontsize=14:x=20:y=h-40:box=1:boxcolor=black@0.5:boxborderw=6" \
  -c:v libx264 -t $DUR -pix_fmt yuv420p -preset medium -crf 20 "$OUT/segments/scene5.mp4" 2>/dev/null

# Scene 6: Inside Tsavo — backward pedestal pull (0:30-0:36)
ffmpeg -y -loop 1 -i "$OUT/resized/Inside Tsavo 5.jpg.jpg" \
  -vf "zoompan=z='if(lte(zoom,1.0),1.15,zoom+0.012)':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d=180:fps=$FPS, \
       drawtext=text='TSAVO HALL: MULTI-PURPOSE AUDITORIUM FOR MAJOR GLOBAL CONVENTIONS':fontcolor=white:fontsize=20:x=20:y=h-80:box=1:boxcolor=black@0.6:boxborderw=8, \
       drawtext=text='2,000 DELEGATES • 4,800m² • 12 BREAKOUT ROOMS':fontcolor=white:fontsize=14:x=20:y=h-40:box=1:boxcolor=black@0.5:boxborderw=6" \
  -c:v libx264 -t $DUR -pix_fmt yuv420p -preset medium -crf 20 "$OUT/segments/scene6.mp4" 2>/dev/null

# Scene 7: Tsavo Barricade — zoom-out wide + brand finale (0:36-0:45)
ffmpeg -y -loop 1 -i "$OUT/resized/Tsavo Barricade Stitch.jpg" \
  -vf "zoompan=z='if(lte(zoom,1.0),1.3,zoom-0.015)':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d=270:fps=$FPS, \
       drawtext=text='KENYATTA INTERNATIONAL CONVENTION CENTRE':fontcolor=#FFCD05:fontsize=36:x=(w-text_w)/2:y=h/2-80:box=1:boxcolor=black@0.7:boxborderw=12, \
       drawtext=text='Est. 1973  •  Nairobi, Kenya':fontcolor=white:fontsize=22:x=(w-text_w)/2:y=h/2:box=1:boxcolor=black@0.6:boxborderw=8, \
       drawtext=text='47 COUNTIES  •  10,000m² EXHIBITION SPACE  •  2,000-SEAT PLENARY':fontcolor=white:fontsize=18:x=(w-text_w)/2:y=h/2+60:box=1:boxcolor=black@0.5:boxborderw=6" \
  -c:v libx264 -t 9 -pix_fmt yuv420p -preset medium -crf 20 "$OUT/segments/scene7.mp4" 2>/dev/null

echo "All scenes created"

echo "=== Concatenating with crossfade ==="
# Create concat file
for f in "$OUT/segments"/scene*.mp4; do
  echo "file '$f'" >> "$OUT/concat.txt"
done

# Use concat demuxer
ffmpeg -y -f concat -safe 0 -i "$OUT/concat.txt" \
  -c:v libx264 -pix_fmt yuv420p -preset medium -crf 20 \
  -vf "fps=$FPS,format=yuv420p,eq=brightness=0.02:contrast=1.1:saturation=1.2" \
  -movflags +faststart \
  "$FINAL" 2>/dev/null

echo "=== Video created ==="
ls -lh "$FINAL"

echo "=== Upload to R2 ==="
scp -o ConnectTimeout=30 "$FINAL" root@167.172.62.234:/tmp/kicc-hero-final.mp4
ssh -o ConnectTimeout=30 root@167.172.62.234 "source /opt/kicc-laravel/.env && \
  R2_AK=\$(grep -E '^CLOUDFLARE_R2_ACCESS_KEY=' /opt/kicc-laravel/.env | cut -d= -f2) && \
  R2_SK=\$(grep -E '^CLOUDFLARE_R2_SECRET_KEY=' /opt/kicc-laravel/.env | cut -d= -f2) && \
  R2_EP=\$(grep -E '^CLOUDFLARE_R2_ENDPOINT=' /opt/kicc-laravel/.env | cut -d= -f2) && \
  R2_BK=\$(grep -E '^CLOUDFLARE_R2_BUCKET=' /opt/kicc-laravel/.env | cut -d= -f2) && \
  export RCLONE_S3_PROVIDER=Cloudflare && \
  export RCLONE_S3_ENDPOINT=\$R2_EP && \
  export RCLONE_S3_ACCESS_KEY_ID=\$R2_AK && \
  export RCLONE_S3_SECRET_ACCESS_KEY=\$R2_SK && \
  export RCLONE_S3_ACL=private && \
  rclone copyto /tmp/kicc-hero-final.mp4 ':s3:'\$R2_BK/kicc/4d/kicc_hero.mp4 2>&1 && \
  echo 'UPLOADED'" 2>&1 | tail -1

echo "=== Verify ==="
curl -s --max-time 10 -o /dev/null -w "Hero video: %{http_code}\n" "https://kicc-r2-media.techhubltd254.workers.dev/storage/kicc/4d/kicc_hero.mp4"