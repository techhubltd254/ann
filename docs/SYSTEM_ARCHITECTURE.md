# KENYA 3D PLATFORM — COMPLETE SYSTEM ARCHITECTURE

## SBS 3D + INTERACTIVE SPLATTING + PUBG-LIKE CONTROL

```
                        USER (web/mobile / VR headset)
                              |
                        +-----+-----+
                        |  Laravel   |  Auth, billing, dashboard, queue
                        |  Portal    |
                        +-----+-----+
                              |
                     +-------+--------+
                     |    n8n.io      |  Visual workflow automation
                     |  Orchestrator  |  Triggers, webhooks, conditionals
                     +-------+--------+  Replace hardcoded glue with nodes
                             |
               +-------------+-------------+
               |     Kimi K3 (OpenRouter)   |  Scene intelligence layer
               |     ~$0.50 per analysis    |
               |                            |
               |  Decides WHICH pipeline:   |
               |  +-- "Static hall" -> Splat  |
               |  +-- "Booth close-up" -> SBS |
               |  +-- "Walkthrough" -> Both   |
               +-------------+--------------+
                             |
              +--------------+--------------+
              |         DECISION            |
              |  n8n node: route by scene   |
              +------+--------------+-------+
                     |              |
        +------------+----+  +-----+------------+
        |  PIPELINE A     |  |  PIPELINE B       |
        |  PASSIVE SBS 3D |  |  INTERACTIVE 3D   |
        +--------+-------+  +------+------------+
                 |                  |
    +------------+------+  +-------+-----------+
    | Nano Banana 2     |  | Nano Banana 2      |
    | Keyframe upscale  |  | Keyframe enhance   |
    +--------+----------+  +-------+-----------+
             |                     |
    +--------+----------+  +-------+-----------+
    | GPU CLUSTER       |  | GPU CLUSTER        |
    | (Vast.ai/RunPod)  |  | (Vast.ai/RunPod)   |
    |                   |  |                    |
    | +---+ +---+ +--+ |  | +---+ +---+ +--+  |
    | |D  | |D  | |D | |  | |GS | |GS | |GS|  |
    | |Av2| |Av2| |Av| |  | |plat| |plat| |pla|  |
    | |   | |   | |2 | |  | |    | |    | |t |  |
    | +---+ +---+ +--+ |  | +---+ +---+ +--+  |
    |      Stereo       |  |  3D reconstruction |
    |  + OpenCV remap   |  |  Gaussian Splatting |
    +--------+----------+  +-------+-----------+
             |                     |
    +--------+----------+  +-------+-----------+
    | DaVinci Resolve   |  | WebGL Player      |
    | API               |  | (Three.js/PlayCanvas)
    | Color grade       |  |                   |
    | Branding overlay  |  | User drags to look |
    | SBS export        |  | Click booths for   |
    | H.265 VR ready    |  | embedded SBS video |
    +--------+----------+  +-------+-----------+
             |                     |
    +--------+----------+  +-------+-----------+
    | Google Flow (Veo) |  | Google Flow (Veo)  |
    | Promo clip from   |  | Flythrough trailer |
    | extracted frames  |  | from splat data    |
    +--------+----------+  +-------+-----------+
             |                     |
             +---------+-----------+
                       |
              +--------+--------+
              |   S3 / CDN      |  Delivery to user
              |  + Email link   |
              +-----------------+
```

---

## THE PUBG/CALL OF DUTY INTERACTION MODEL

```
USER OPENS LINK ON PHONE
         |
         v
  +--------------+
  |  WebGL View  |  Unity-like scene loads in browser
  |  (no app install)
  +------+-------+
         |
  +------+-------+
  | Drag screen  |  -> Camera rotates around exhibition hall
  | Pinch        |  -> Zoom into booth details
  | Tap booth    |  -> Opens info card + plays SBS 3D video
  | Gyroscope    |  -> VR mode: look around by turning phone
  | Gamepad      |  -> WASD movement like PUBG
  +------+-------+
         |
         v
  USER FEELS LIKE THEY'RE
  WALKING THROUGH KICC
  - See depth between pillars and displays
  - Lean into booths
  - Watch embedded 3D videos in-context
  - Share live link: "come join this hall"
```

---

## THE 3-TIER PRODUCT

| Tier | Output | What User Gets | Tech | Price per min |
|------|--------|---------------|------|--------------|
| **Bronze** | SBS 3D video | Watch on VR headset | DAv2 + Stereo + DaVinci | **$5** |
| **Silver** | Interactive 3D link | Walk through hall on phone, drag to look | Gaussian Splatting + WebGL | **$15** |
| **Gold** | Interactive + Embedded SBS | Full PUBG-like experience with booth videos | Splatting + SBS hybrid + Google Flow trailer | **$30** |

---

## THE n8n WORKFLOW (visual nodes)

```
[Webhook: file uploaded]
    |
[Kimi K3: analyze]
    |
[Switch node: scene type?]
    +-- "exhibition_hall" -> Queue splatting
    +-- "booth_close_up"  -> Queue SBS
    +-- "walkthrough"     -> Fork -> both splatting + SBS
    |
[GPU node: process on Vast]
    |
[DaVinci node: color grade]
    |
[Google Flow node: generate trailer]
    |
[S3 node: upload]
    |
[Email node: send link]
```

---

## WHAT TO BUILD (file tree)

```
kenya-3d-platform/
+-- laravel-backend/           # User portal + API + billing
+-- n8n-workflows/             # n8n JSON exports for every pipeline
|   +-- sbs-pipeline.json
|   +-- splatting-pipeline.json
|   +-- hybrid-pipeline.json
+-- pipeline/
|   +-- analyzer.py            # Kimi K3 scene analysis
|   +-- depth_worker.py        # DAv2 depth on GPU
|   +-- stereo_worker.py       # OpenCV SBS generation
|   +-- splat_worker.py        # 3D Gaussian Splatting
|   +-- webgl_viewer/          # Three.js interactive player
|   |   +-- index.html
|   |   +-- player.js
|   |   +-- booth-overlay.js
|   +-- refiner.py             # Nano Banana 2 keyframe enhance
|   +-- resolver.py            # DaVinci Resolve API
|   +-- flow_adapter.py        # Google Flow API
|   +-- orchestrator.py        # Master controller
+-- deploy/                    # Vast.ai auto-deploy scripts
+-- opencode/                  # AGENTS.md + project context
```

---

## THE SOFTWARE STACK

| Layer | Technology | Role |
|-------|-----------|------|
| Frontend | Laravel + Vue | Upload, pay, download |
| AI Analysis | Kimi K3 (OpenRouter API) | Scene understanding |
| Image Refine | Nano Banana 2 (Google API) | Keyframe upscale |
| Depth | Depth Anything V2 (local GPU) | Depth maps |
| Stereo | Python/OpenCV (local GPU) | SBS generation |
| 3D Reconstruction | Gaussian Splatting (local GPU) | Interactive 3D scenes |
| WebGL Viewer | Three.js / PlayCanvas | Browser-based 3D interaction |
| Post-pro | DaVinci Resolve API | Grading + branding |
| Promo | Google Flow (Veo API) | Marketing clips |
| Automation | n8n (self-hosted) | Visual workflow orchestration |
| Orchestration | Laravel Queues + Python drivers | Pipeline glue |
| Compute | Vast.ai / RunPod | GPU rental |
| Storage | S3 / DigitalOcean Spaces | File delivery |
| Monitoring | Prometheus + Grafana | GPU utilization, queue depth |

---

## SUBSCRIPTION COST SUMMARY

| Service | Purpose | Monthly |
|---------|---------|---------|
| **OpenCode Go** | AI assistant codes the system with you | **$10** |
| **OpenRouter (Kimi K3)** | Scene intelligence per video | ~$50/100 videos |
| **Google AI Pro** | Nano Banana 2 + Google Flow | **$20** |
| **Vast.ai GPUs** | Splatting + depth (as needed) | ~$500-2000 |
| **n8n (self-hosted)** | Visual workflow automation | **Free** |
| **Laravel Forge** | Server management | **$15** |
| **DigitalOcean/S3** | Storage + CDN | ~$50 |
| **DaVinci Resolve** | Post-pro (one-time $295) | N/A |
| **Total recurring** | | **~$95 + GPU** |

---

*Generated for Kenya National Exhibition Platform — Immersive 3D Content Pipeline*
