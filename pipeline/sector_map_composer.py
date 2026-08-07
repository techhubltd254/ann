from __future__ import annotations

import base64
import json
import time
from io import BytesIO
from pathlib import Path

from PIL import Image

MOMBASA_PHOTOS_DIR = Path("/home/kicc/Desktop/kicc/m&k_labeled/Mombasa")
KILIFI_PHOTOS_DIR = Path("/home/kicc/Desktop/kicc/m&k_labeled/Kilifi")
OUTPUT_DIR = Path(__file__).resolve().parent / "webgl_viewer"
TEMPLATE_PATH = Path(__file__).resolve().parent / "sector_map_template.html"


def _image_to_base64(path: str, max_width: int = 320) -> str:
    img = Image.open(path)
    if img.width > max_width:
        ratio = max_width / img.width
        img = img.resize((max_width, int(img.height * ratio)), Image.LANCZOS)
    buf = BytesIO()
    img.save(buf, format="JPEG", quality=75)
    b64 = base64.b64encode(buf.getvalue()).decode()
    return f"data:image/jpeg;base64,{b64}"


def _load_sector_photos(directory: Path, max_photos: int = 16) -> list[dict]:
    photos = sorted(directory.glob("*.*g"))[:max_photos]
    result = []
    for p in photos:
        stem = p.stem.replace("_", " ").replace("Kilifi ", "").replace("Mombasa ", "")
        result.append({
            "name": stem,
            "b64": _image_to_base64(str(p), max_width=320),
        })
    return result


def compose_sector_map() -> str:
    mombasa = _load_sector_photos(MOMBASA_PHOTOS_DIR)
    kilifi = _load_sector_photos(KILIFI_PHOTOS_DIR)
    print(f"  Mombasa: {len(mombasa)} sector photos")
    print(f"  Kilifi:  {len(kilifi)} sector photos")

    counties_json = json.dumps([
        {"name": "Mombasa", "photos": mombasa},
        {"name": "Kilifi", "photos": kilifi},
    ], ensure_ascii=False)

    if not TEMPLATE_PATH.exists():
        html = _generate_html(counties_json)
    else:
        html = TEMPLATE_PATH.read_text(encoding="utf-8").replace(
            "/* COUNTY_JSON_PLACEHOLDER */",
            f"const COUNTIES_DATA = {counties_json};"
        )

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    out_path = OUTPUT_DIR / "sector_map.html"
    out_path.write_text(html, encoding="utf-8")

    size_kb = len(html.encode()) / 1024
    print(f"  Output:  {out_path}")
    print(f"  Size:    {size_kb:.0f} KB")
    return str(out_path)


def _generate_html(counties_json: str) -> str:
    lines = []
    def L(s=""):
        lines.append(s)

    L('<!DOCTYPE html>')
    L('<html lang="en">')
    L('<head>')
    L('<meta charset="UTF-8">')
    L('<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">')
    L('<title>Mombasa & Kilifi — Sector 3D Explorer</title>')
    L('<style>')
    L('  * { margin: 0; padding: 0; box-sizing: border-box; }')
    L('  body { background: #0a0a12; overflow: hidden; font-family: "Segoe UI", system-ui, sans-serif; touch-action: none; }')
    L('  canvas { display: block; }')
    L('  #loading { position: fixed; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #0a0a12; z-index: 1000; color: #fff; transition: opacity 0.8s; }')
    L('  #loading .spinner { width: 56px; height: 56px; border: 4px solid rgba(255,215,0,0.15); border-top-color: #FFD700; border-radius: 50%; animation: spin 1s linear infinite; }')
    L('  @keyframes spin { to { transform: rotate(360deg); } }')
    L('  #loading h1 { margin-top: 24px; font-size: 20px; color: #FFD700; }')
    L('  #back-link { position: fixed; top: 16px; left: 16px; z-index: 200; color: rgba(255,255,255,0.4); font-size: 13px; text-decoration: none; background: rgba(0,0,0,0.5); padding: 6px 14px; border-radius: 16px; backdrop-filter: blur(4px); }')
    L('  #back-link:hover { color: #FFD700; }')
    L('  #header { position: fixed; top: 0; left: 0; width: 100%; padding: 16px 20px; z-index: 100; pointer-events: none; text-align: center; }')
    L('  #header h1 { color: #FFD700; font-size: 16px; font-weight: 700; text-shadow: 0 2px 12px rgba(0,0,0,0.9); }')
    L('  #header .sub { color: rgba(255,255,255,0.3); font-size: 11px; }')
    L('  #sector-popup { position: fixed; bottom: 0; left: 0; width: 100%; z-index: 200; background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.7) 70%, transparent 100%); padding: 60px 24px 24px; transform: translateY(100%); transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1); pointer-events: none; }')
    L('  #sector-popup.open { transform: translateY(0); }')
    L('  #sector-popup > * { pointer-events: auto; }')
    L('  #sector-popup .content { max-width: 600px; margin: 0 auto; }')
    L('  #sector-popup h2 { color: #FFD700; font-size: 18px; margin-bottom: 2px; }')
    L('  #sector-popup .county { color: rgba(255,255,255,0.35); font-size: 12px; }')
    L('  #sector-popup .close { position: absolute; top: 16px; right: 16px; width: 32px; height: 32px; background: rgba(255,255,255,0.1); border: none; border-radius: 50%; color: #fff; font-size: 18px; cursor: pointer; }')
    L('  #sector-popup img { width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px; margin-top: 8px; }')
    L('  #controls-hint { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); color: rgba(255,255,255,0.3); font-size: 12px; z-index: 100; background: rgba(0,0,0,0.5); padding: 6px 16px; border-radius: 16px; transition: opacity 2s; }')
    L('  #legend { position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%); z-index: 100; display: flex; gap: 20px; background: rgba(0,0,0,0.5); padding: 6px 16px; border-radius: 20px; }')
    L('  #legend span { font-size: 11px; color: rgba(255,255,255,0.5); }')
    L('  #legend .m { color: #66aaff; }')
    L('  #legend .k { color: #66dd88; }')
    L('</style>')
    L('</head>')
    L('<body>')
    L('<div id="loading">')
    L('  <div class="spinner"></div>')
    L('  <h1>Mombasa & Kilifi</h1>')
    L('  <p style="margin-top:8px;font-size:13px;opacity:0.5;">Loading sector explorer...</p>')
    L('</div>')
    L('<a id="back-link" href="kenya_3d_map.html">&larr; 47 Counties Map</a>')
    L('<div id="header">')
    L('  <h1>Mombasa &middot; Kilifi &mdash; Sector Explorer</h1>')
    L('  <div class="sub">Tap a sector photo to explore</div>')
    L('</div>')
    L('<div id="legend"><span class="m">&#9679; Mombasa</span><span class="k">&#9679; Kilifi</span></div>')
    L('<div id="controls-hint">&#128187; Drag to rotate &middot; Scroll to zoom &middot; Tap a photo</div>')
    L('<div id="sector-popup">')
    L('  <button class="close" onclick="closePopup()">&times;</button>')
    L('  <div class="content">')
    L('    <h2 id="popup-name"></h2>')
    L('    <div class="county" id="popup-county"></div>')
    L('    <img id="popup-img" src="" alt="">')
    L('  </div>')
    L('</div>')
    L('<script type="importmap">')
    L(json.dumps({
        "imports": {
            "three": "https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.js",
            "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/"
        }
    }))
    L('</script>')
    L('<script type="module">')
    L("import * as THREE from 'three';")
    L("import { OrbitControls } from 'three/addons/controls/OrbitControls.js';")
    L("import { CSS2DRenderer, CSS2DObject } from 'three/addons/renderers/CSS2DRenderer.js';")
    L('')
    L(f'const COUNTIES_DATA = {counties_json};')
    L('')
    L("""
let scene, camera, renderer, controls, labelRenderer;
const billboards = [];
const raycaster = new THREE.Raycaster();
const mouse = new THREE.Vector2();

function init() {
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0x0d0d18);
  scene.fog = new THREE.Fog(0x0d0d18, 10, 25);

  camera = new THREE.PerspectiveCamera(50, window.innerWidth / window.innerHeight, 0.1, 50);
  camera.position.set(0, 2.0, 10);

  renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 0.8;
  document.body.appendChild(renderer.domElement);

  controls = new OrbitControls(camera, renderer.domElement);
  controls.target.set(0, 0.5, 0);
  controls.enableDamping = true;
  controls.dampingFactor = 0.08;
  controls.minDistance = 3;
  controls.maxDistance = 20;
  controls.maxPolarAngle = Math.PI / 2.2;
  controls.update();

  // Lights
  const hemi = new THREE.HemisphereLight(0x87ceeb, 0x1a1a3a, 0.6);
  scene.add(hemi);
  const sun = new THREE.DirectionalLight(0xffeedd, 1.5);
  sun.position.set(5, 8, 6);
  sun.castShadow = true;
  scene.add(sun);
  const fill = new THREE.DirectionalLight(0x4488ff, 0.4);
  fill.position.set(-4, 2, -3);
  scene.add(fill);
  const rim = new THREE.DirectionalLight(0xff8844, 0.2);
  rim.position.set(-2, 1, -5);
  scene.add(rim);

  // Stars
  const starsGeo = new THREE.BufferGeometry();
  const starVerts = [];
  for (let i = 0; i < 2000; i++) {
    starVerts.push((Math.random() - 0.5) * 200, (Math.random() - 0.5) * 200, (Math.random() - 0.5) * 200);
  }
  starsGeo.setAttribute('position', new THREE.Float32BufferAttribute(starVerts, 3));
  scene.add(new THREE.Points(starsGeo, new THREE.PointsMaterial({ color: 0xffffff, size: 0.15, transparent: true, opacity: 0.6 })));

  // Ground glow
  scene.add(new THREE.Mesh(
    new THREE.PlaneGeometry(30, 20),
    new THREE.MeshStandardMaterial({ color: 0x0d0d18, roughness: 0.9 })
  ).rotateX(-Math.PI / 2).translateY(-0.05));

  // County labels (CSS2D)
  labelRenderer = new CSS2DRenderer();
  labelRenderer.setSize(window.innerWidth, window.innerHeight);
  labelRenderer.domElement.style.position = 'absolute';
  labelRenderer.domElement.style.top = '0';
  labelRenderer.domElement.style.pointerEvents = 'none';
  document.body.appendChild(labelRenderer.domElement);

  function createCountyLabel(text, color, x, z) {
    const div = document.createElement('div');
    div.textContent = text;
    div.style.color = color;
    div.style.fontSize = '20px';
    div.style.fontWeight = '700';
    div.style.textShadow = '0 2px 12px rgba(0,0,0,0.9)';
    div.style.background = 'rgba(0,0,0,0.5)';
    div.style.padding = '4px 16px';
    div.style.borderRadius = '20px';
    div.style.border = '1px solid ' + color;
    const label = new CSS2DObject(div);
    label.position.set(x, -0.3, z);
    return label;
  }

  // Place billboards
  COUNTIES_DATA.forEach((county, ci) => {
    const isMombasa = county.name === 'Mombasa';
    const color = isMombasa ? 0x66aaff : 0x66dd88;
    const hexColor = isMombasa ? '#66aaff' : '#66dd88';
    const centerX = isMombasa ? -2.8 : 2.8;
    const photos = county.photos;
    const count = photos.length;
    const radius = 3.0;
    const arcStart = -Math.PI * 0.6;
    const arcEnd = Math.PI * 0.6;

    scene.add(createCountyLabel(county.name, hexColor, centerX, -1.0));

    photos.forEach((photo, i) => {
      const frac = count > 1 ? i / (count - 1) : 0.5;
      const angle = arcStart + frac * (arcEnd - arcStart);
      const bx = centerX + Math.cos(angle) * radius;
      const bz = Math.sin(angle) * radius;
      const by = 0.5 + 0.15 * Math.sin(i * 0.7);

      const img = new Image();
      img.src = photo.b64;
      const tex = new THREE.Texture(img);
      tex.colorSpace = THREE.SRGBColorSpace;
      img.onload = () => { tex.needsUpdate = true; };

      const aspect = 16 / 9;
      const w = 1.0;
      const h = w / aspect;

      const mat = new THREE.MeshBasicMaterial({
        map: tex, side: THREE.DoubleSide, transparent: true,
      });
      const mesh = new THREE.Mesh(new THREE.PlaneGeometry(w, h), mat);
      mesh.position.set(bx, by, bz);
      mesh.lookAt(0, by, 0);

      const frame = new THREE.Mesh(
        new THREE.PlaneGeometry(w + 0.06, h + 0.06),
        new THREE.MeshBasicMaterial({ color, transparent: true, opacity: 0.3, side: THREE.BackSide })
      );
      frame.position.copy(mesh.position);
      frame.quaternion.copy(mesh.quaternion);
      scene.add(frame);
      scene.add(mesh);

      const marker = new THREE.Mesh(
        new THREE.CylinderGeometry(0.04, 0.06, 0.2, 6),
        new THREE.MeshBasicMaterial({ color })
      );
      marker.position.set(bx, 0.1, bz);
      scene.add(marker);

      billboards.push({ mesh, frame, data: photo, county: county.name, baseY: by, phase: i * 0.3 });
    });
  });

  // Click handler
  renderer.domElement.addEventListener('click', (e) => {
    mouse.x = (e.clientX / window.innerWidth) * 2 - 1;
    mouse.y = -(e.clientY / window.innerHeight) * 2 + 1;
    raycaster.setFromCamera(mouse, camera);
    const targets = billboards.map(b => b.mesh);
    const hits = raycaster.intersectObjects(targets);
    if (hits.length > 0) {
      const found = billboards.find(b => b.mesh === hits[0].object);
      if (found) openSector(found);
    }
  });

  renderer.domElement.addEventListener('touchstart', (e) => {
    const t = e.changedTouches[0];
    mouse.x = (t.clientX / window.innerWidth) * 2 - 1;
    mouse.y = -(t.clientY / window.innerHeight) * 2 + 1;
    raycaster.setFromCamera(mouse, camera);
    const targets = billboards.map(b => b.mesh);
    const hits = raycaster.intersectObjects(targets);
    if (hits.length > 0) {
      const found = billboards.find(b => b.mesh === hits[0].object);
      if (found) openSector(found);
    }
  }, { passive: true });

  window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
    labelRenderer.setSize(window.innerWidth, window.innerHeight);
  });

  document.getElementById('loading').style.display = 'none';
  setTimeout(() => {
    const hint = document.getElementById('controls-hint');
    if (hint) hint.style.opacity = '0';
  }, 4000);

  animate();
}

function openSector(sector) {
  const popup = document.getElementById('sector-popup');
  document.getElementById('popup-name').textContent = sector.data.name;
  document.getElementById('popup-county').textContent = sector.county;
  document.getElementById('popup-img').src = sector.data.b64;
  popup.classList.add('open');
}

window.closePopup = () => {
  document.getElementById('sector-popup').classList.remove('open');
};

function animate() {
  requestAnimationFrame(animate);
  controls.update();
  const t = Date.now() * 0.0004;
  billboards.forEach((b) => {
    b.mesh.position.y = b.baseY + 0.06 * Math.sin(t + b.phase);
    b.frame.position.copy(b.mesh.position);
  });
  renderer.render(scene, camera);
  labelRenderer.render(scene, camera);
}

init();
""")
    L('</script>')
    L('</body>')
    L('</html>')

    return "\n".join(lines)


if __name__ == "__main__":
    t0 = time.time()
    print("=" * 48)
    print("  KICC Sector Sub-Map Generator")
    print("=" * 48)
    compose_sector_map()
    print(f"  Done in {time.time() - t0:.1f}s")
