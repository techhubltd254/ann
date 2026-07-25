from fpdf import FPDF

class PDF(FPDF):
    def header(self):
        self.set_font('Helvetica', 'I', 8)
        self.cell(0, 5, 'Kenya 3D Platform - Complete System Architecture', 0, 1, 'C')
        self.line(10, 10, 200, 10)
        self.ln(3)
    def footer(self):
        self.set_y(-15)
        self.set_font('Helvetica', 'I', 8)
        self.cell(0, 10, f'Page {self.page_no()}/{{nb}}', 0, 0, 'C')
    def section_title(self, title):
        self.set_font('Helvetica', 'B', 14)
        self.set_text_color(0, 51, 102)
        self.cell(0, 10, title, 0, 1, 'L')
        self.set_draw_color(0, 51, 102)
        self.line(10, self.get_y(), 200, self.get_y())
        self.ln(3)
    def sub_title(self, title):
        self.set_font('Helvetica', 'B', 11)
        self.set_text_color(51, 51, 51)
        self.cell(0, 8, title, 0, 1, 'L')
        self.ln(1)
    def body_text(self, txt):
        self.set_font('Courier', '', 8)
        self.set_text_color(0, 0, 0)
        self.multi_cell(0, 4, txt)
        self.ln(1)
    def table_header(self, cols, widths):
        self.set_font('Helvetica', 'B', 9)
        self.set_fill_color(0, 51, 102)
        self.set_text_color(255, 255, 255)
        for i, col in enumerate(cols):
            self.cell(widths[i], 7, col, 1, 0, 'C', 1)
        self.ln()
    def table_row(self, cols, widths):
        self.set_font('Helvetica', '', 8)
        self.set_text_color(0, 0, 0)
        for i, col in enumerate(cols):
            self.cell(widths[i], 6, col, 1, 0, 'C')
        self.ln()

pdf = PDF('P', 'mm', 'A4')
pdf.alias_nb_pages()
pdf.set_auto_page_break(auto=True, margin=20)
pdf.add_page()

# Title
pdf.set_font('Helvetica', 'B', 22)
pdf.set_text_color(0, 51, 102)
pdf.cell(0, 15, 'KENYA 3D PLATFORM', 0, 1, 'C')
pdf.set_font('Helvetica', '', 12)
pdf.set_text_color(100, 100, 100)
pdf.cell(0, 8, 'SBS 3D + Interactive Splatting + PUBG-like Control', 0, 1, 'C')
pdf.ln(5)

# ============================================================
pdf.section_title('1. COMPLETE SYSTEM ARCHITECTURE')
# ============================================================

arch = """
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
                     +-------+--------+
                             |
               +-------------+-------------+
               |     Kimi K3 (OpenRouter)   |  Scene intelligence
               |     ~$0.50 per analysis    |
               |  Decides WHICH pipeline:   |
               |  +-- "Static hall" -> Splat|
               |  +-- "Booth close-up"-> SBS|
               |  +-- "Walkthrough" -> Both |
               +-------------+-------------+
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
    | +---+ +---+ +--+ |  | +---+ +---+ +--+  |
    | |DAv2|DAv2|DAv2| |  | |Splat|Splat|Splat||
    | +---+ +---+ +--+ |  | +---+ +---+ +--+  |
    |  Stereo + remap  |  |  3D Gaussian Splat |
    +--------+----------+  +-------+-----------+
             |                     |
    +--------+----------+  +-------+-----------+
    | DaVinci Resolve   |  | WebGL Player      |
    | API               |  | (Three.js)        |
    | Color + branding  |  | User drags to look|
    | SBS export H.265  |  | Click booths ->   |
    |                   |  | embedded SBS video |
    +--------+----------+  +-------+-----------+
             |                     |
    +--------+----------+  +-------+-----------+
    | Google Flow (Veo) |  | Google Flow (Veo)  |
    | Promo clip        |  | Flythrough trailer |
    +--------+----------+  +-------+-----------+
             |                     |
             +---------+-----------+
                       |
              +--------+--------+
              |   S3 / CDN      |  Delivery to user
              |  + Email link   |
              +-----------------+
"""

pdf.body_text(arch)

# ============================================================
pdf.section_title('2. THE PUBG/CALL OF DUTY INTERACTION MODEL')
# ============================================================

interaction = """
USER OPENS LINK ON PHONE
         |
         v
  +--------------+
  |  WebGL View  |  Unity-like scene loads in browser (no app install)
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
  USER FEELS LIKE THEY'RE WALKING THROUGH KICC
  - See depth between pillars and displays
  - Lean into booths
  - Watch embedded 3D videos in-context
  - Share live link: "come join this hall"
"""

pdf.body_text(interaction)

# ============================================================
pdf.section_title('3. THE 3-TIER PRODUCT')
# ============================================================

pdf.sub_title('Pricing & Delivery Tiers')
cols = ['Tier', 'Output', 'What User Gets', 'Tech', 'Price/min']
widths = [18, 35, 50, 55, 22]
pdf.table_header(cols, widths)
rows = [
    ['Bronze', 'SBS 3D video', 'Watch on VR headset', 'DAv2 + Stereo + DaVinci', '$5'],
    ['Silver', 'Interactive 3D link', 'Walk through hall on phone', 'Splatting + WebGL', '$15'],
    ['Gold', 'Interactive + Embedded SBS', 'PUBG-like with booth videos', 'Splatting + SBS + Flow trailer', '$30'],
]
for r in rows:
    pdf.table_row(r, widths)

# ============================================================
pdf.section_title('4. THE n8n WORKFLOW')
# ============================================================

n8n = """
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
"""

pdf.body_text(n8n)

# ============================================================
pdf.section_title('5. PROJECT FILE TREE')
# ============================================================

tree = """
kenya-3d-platform/
+-- laravel-backend/           User portal + API + billing
+-- n8n-workflows/             n8n JSON exports for every pipeline
|   +-- sbs-pipeline.json
|   +-- splatting-pipeline.json
|   +-- hybrid-pipeline.json
+-- pipeline/
|   +-- analyzer.py            Kimi K3 scene analysis
|   +-- depth_worker.py        DAv2 depth on GPU
|   +-- stereo_worker.py       OpenCV SBS generation
|   +-- splat_worker.py        3D Gaussian Splatting
|   +-- webgl_viewer/          Three.js interactive player
|   |   +-- index.html
|   |   +-- player.js
|   |   +-- booth-overlay.js
|   +-- refiner.py             Nano Banana 2 keyframe enhance
|   +-- resolver.py            DaVinci Resolve API
|   +-- flow_adapter.py        Google Flow API
|   +-- orchestrator.py        Master controller
+-- deploy/                    Vast.ai auto-deploy scripts
+-- opencode/                  AGENTS.md + project context
"""

pdf.body_text(tree)

# ============================================================
pdf.section_title('6. THE SOFTWARE STACK')
# ============================================================

cols2 = ['Layer', 'Technology', 'Role']
widths2 = [40, 60, 80]
pdf.table_header(cols2, widths2)
stack_rows = [
    ['Frontend', 'Laravel + Vue', 'Upload, pay, download'],
    ['AI Analysis', 'Kimi K3 (OpenRouter)', 'Scene understanding'],
    ['Image Refine', 'Nano Banana 2 (Google)', 'Keyframe upscale'],
    ['Depth', 'Depth Anything V2 (GPU)', 'Depth maps'],
    ['Stereo', 'Python/OpenCV (GPU)', 'SBS generation'],
    ['3D Reconstruction', 'Gaussian Splatting', 'Interactive 3D scenes'],
    ['WebGL Viewer', 'Three.js / PlayCanvas', 'Browser 3D interaction'],
    ['Post-pro', 'DaVinci Resolve API', 'Grading + branding'],
    ['Promo', 'Google Flow (Veo API)', 'Marketing clips'],
    ['Automation', 'n8n (self-hosted)', 'Visual workflow orchestration'],
    ['Orchestration', 'Laravel Queues + Python', 'Pipeline glue'],
    ['Compute', 'Vast.ai / RunPod', 'GPU rental'],
    ['Storage', 'S3 / DigitalOcean', 'File delivery'],
    ['Monitoring', 'Prometheus + Grafana', 'GPU, queue depth'],
]
for r in stack_rows:
    pdf.table_row(r, widths2)

# ============================================================
pdf.section_title('7. SUBSCRIPTION COST SUMMARY')
# ============================================================

cols3 = ['Service', 'Purpose', 'Monthly']
widths3 = [50, 80, 40]
pdf.table_header(cols3, widths3)
cost_rows = [
    ['OpenCode Go', 'AI assistant codes the system', '$10'],
    ['OpenRouter (Kimi K3)', 'Scene intelligence per video', '~$50/100 videos'],
    ['Google AI Pro', 'Nano Banana 2 + Google Flow', '$20'],
    ['Vast.ai GPUs', 'Splatting + depth (as needed)', '~$500-2000'],
    ['n8n (self-hosted)', 'Visual workflow automation', 'Free'],
    ['Laravel Forge', 'Server management', '$15'],
    ['DigitalOcean/S3', 'Storage + CDN', '~$50'],
    ['DaVinci Resolve', 'Post-pro (one-time)', '$295 (once)'],
]
for r in cost_rows:
    pdf.table_row(r, widths3)

pdf.set_font('Helvetica', 'B', 10)
pdf.set_text_color(0, 51, 102)
pdf.cell(0, 8, 'Total recurring: ~$95/mo + GPU compute ($500-2000)', 0, 1, 'R')

pdf.ln(10)
pdf.set_font('Helvetica', 'I', 9)
pdf.set_text_color(120, 120, 120)
pdf.cell(0, 6, 'Generated for Kenya National Exhibition Platform - Immersive 3D Content Pipeline', 0, 1, 'C')

pdf.output('C:\\Users\\lolen\\Desktop\\kicc\\SYSTEM_ARCHITECTURE.pdf')
print("PDF generated: C:\\Users\\lolen\\Desktop\\kicc\\SYSTEM_ARCHITECTURE.pdf")
