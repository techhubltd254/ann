"""Three.js Interactive 47-County Map Generator.

Generates a complete HTML/JS web page that displays all 47 Kenyan counties
on an interactive 3D map using Three.js (loaded from CDN).

Each county is:
- A region marker on the map (color-coded by scene type)
- A billboard showing the county photo
- Clickable for info panel

Output: kenya_3d_map.html — fully self-contained, no build step needed.
"""

from __future__ import annotations

import base64
import json
import os
from pathlib import Path
from typing import Any

from county_data import COUNTIES, REGION_COLORS, SCENE_TYPE_COLORS, hex_to_rgb
from terrain_gen import image_to_3d_terrain, GRID_SIZE

COUNTY_PHOTOS_DIR = Path("/home/kicc/Desktop/kicc/county profile pics labeled")
MOMBASA_PHOTOS_DIR = Path("/home/kicc/Desktop/kicc/m&k_labeled/Mombasa")
KILIFI_PHOTOS_DIR = Path("/home/kicc/Desktop/kicc/m&k_labeled/Kilifi")
OUTPUT_DIR = Path("/home/kicc/Desktop/kicc/kenya-3d-platform/pipeline/webgl_viewer")


def _photo_path(county_name: str) -> str | None:
    """Find the county photo file, handling underscores/spaces."""
    candidates = [
        f"{county_name}.jpg",
        f"{county_name.replace(' ', '_')}.jpg",
        f"{county_name.replace(' ', '')}.jpg",
    ]
    for c in candidates:
        p = COUNTY_PHOTOS_DIR / c
        if p.exists():
            return str(p)
    return None


def _image_to_base64(path: str, max_width: int = 512) -> str:
    """Convert an image to a base64 data URL, resized to max_width for fast loading."""
    from PIL import Image
    import io
    img = Image.open(path).convert("RGB")
    w, h = img.size
    if w > max_width:
        ratio = max_width / w
        img = img.resize((max_width, int(h * ratio)), Image.LANCZOS)
    buf = io.BytesIO()
    img.save(buf, format="JPEG", quality=85)
    b64 = base64.b64encode(buf.getvalue()).decode()
    return f"data:image/jpeg;base64,{b64}"


def _sector_photos_to_grid(county: str) -> list[str]:
    """Get base64 thumbnails of sector photos for a county."""
    dir_map = {
        "mombasa": MOMBASA_PHOTOS_DIR,
        "kilifi": KILIFI_PHOTOS_DIR,
    }
    photo_dir = dir_map.get(county.lower())
    if not photo_dir or not photo_dir.exists():
        return []
    photos = sorted(photo_dir.glob("*.*g"))[:6]
    return [_image_to_base64(str(p), max_width=256) for p in photos]


def _region_globe_position(c: dict, scale: float = 1.0) -> tuple[float, float, float]:
    """Convert county map coordinates to 3D positions on a flattened circle layout.

    Kenya is roughly a curved arc. We position counties in a fan-like layout
    radiating from center, grouped by region.
    """
    x = c["x"] * scale
    z = c["z"] * scale
    y = 0
    return (x, y, z)


def _scene_type_color_hex(scene_type: str) -> str:
    return SCENE_TYPE_COLORS.get(scene_type, "#888888")


def _scene_type_label(scene_type: str) -> str:
    labels = {"urban": "Urban", "rural": "Rural", "coastal": "Coastal", "mountain": "Mountain", "arid": "Arid"}
    return labels.get(scene_type, scene_type)


def generate_county_data_json(analyses: list[dict] | None = None, terrain: bool = False) -> list[dict]:
    """Generate the county data JSON for the Three.js viewer."""
    analysis_map = {}
    if analyses:
        for a in analyses:
            name = Path(a["path"]).stem.replace("_", " ").replace(" City", "")
            analysis_map[name.lower()] = a

    invert_map = {"coastal": False, "mountain": False, "arid": False, "rural": False, "urban": False}
    edge_map = {"urban": 0.6, "rural": 0.2, "coastal": 0.3, "arid": 0.4, "mountain": 0.5}

    counties_data = []
    for c in COUNTIES:
        photo = _photo_path(c["name"])
        analysis = analysis_map.get(c["name"].lower(), {})

        if photo:
            photo_b64 = _image_to_base64(photo)
        else:
            photo_b64 = None

        scene_type = analysis.get("scene_type", c["scene_type"])
        palette = analysis.get("dominant_colors", [c["color"]])

        colors = {
            "region": REGION_COLORS.get(c["region"], "#888888"),
            "scene": SCENE_TYPE_COLORS.get(scene_type, "#888888"),
            "dominant": palette,
        }

        px, py, pz = _region_globe_position(c)

        sector_photos = _sector_photos_to_grid(c.get("id", ""))

        terrain_data = None
        if terrain and photo:
            terrain_data = image_to_3d_terrain(
                str(photo),
                grid_size=16,
                invert=invert_map.get(scene_type, False),
                edge_boost=edge_map.get(scene_type, 0.0),
            )

        county_data = {
            "id": c["id"],
            "name": c["name"],
            "capital": c["capital"],
            "region": c["region"],
            "scene_type": scene_type,
            "scene_type_label": _scene_type_label(scene_type),
            "area_km2": c["area_km2"],
            "population": c["population"],
            "sectors": c["sectors"],
            "has_water": analysis.get("has_water", c.get("has_water", False)),
            "brightness": analysis.get("brightness", 0.5),
            "contrast": analysis.get("contrast", 0.3),
            "position": {"x": round(px, 2), "y": round(py, 2), "z": round(pz, 2)},
            "colors": colors,
            "photo_b64": photo_b64,
            "sector_photos": sector_photos,
            "terrain": terrain_data,
        }
        counties_data.append(county_data)

    return counties_data


def generate_region_groups(counties_data: list[dict]) -> dict[str, list[dict]]:
    """Group county data by region."""
    groups = {}
    for c in counties_data:
        r = c["region"]
        if r not in groups:
            groups[r] = []
        groups[r].append(c)
    return groups


def _build_map_html(counties_json: str) -> str:
    """Generate the complete Three.js HTML page for the 47-county interactive map."""
    return f"""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>KICC National Exhibition — 47 Counties 3D Map</title>
<style>
  * {{ margin: 0; padding: 0; box-sizing: border-box; }}
  body {{ background: #0a0a12; overflow: hidden; font-family: 'Segoe UI', system-ui, sans-serif; touch-action: none; }}
  canvas {{ display: block; }}
  #loading {{
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    background: #0a0a12; z-index: 1000; color: #fff; transition: opacity 0.8s;
  }}
  #loading .spinner {{
    width: 56px; height: 56px; border: 4px solid rgba(255,215,0,0.15);
    border-top-color: #FFD700; border-radius: 50%; animation: spin 1s linear infinite;
  }}
  @keyframes spin {{ to {{ transform: rotate(360deg); }} }}
  #loading h1 {{ margin-top: 24px; font-size: 22px; color: #FFD700; }}
  #loading p {{ margin-top: 8px; font-size: 13px; opacity: 0.5; }}
  #header {{
    position: fixed; top: 0; left: 0; width: 100%; padding: 16px 20px;
    display: flex; justify-content: space-between; align-items: center;
    z-index: 100; pointer-events: none;
  }}
  #header > * {{ pointer-events: auto; }}
  #header h1 {{ color: #FFD700; font-size: 17px; font-weight: 700; text-shadow: 0 2px 12px rgba(0,0,0,0.9); }}
  #header .sub {{ color: rgba(255,255,255,0.4); font-size: 11px; }}
  #legend {{
    position: fixed; bottom: 80px; left: 16px; z-index: 100;
    background: rgba(0,0,0,0.75); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.08); border-radius: 10px;
    padding: 12px 16px; min-width: 130px;
  }}
  #legend h3 {{ color: rgba(255,255,255,0.5); font-size: 10px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }}
  #legend .item {{ display: flex; align-items: center; gap: 8px; margin-bottom: 4px; font-size: 11px; color: rgba(255,255,255,0.7); }}
  #legend .dot {{ width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }}
  #controls-hint {{
    position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);
    color: rgba(255,255,255,0.3); font-size: 12px; z-index: 100;
    background: rgba(0,0,0,0.5); padding: 6px 16px; border-radius: 16px;
    transition: opacity 2s;
  }}
  #county-panel {{
    position: fixed; bottom: 0; left: 0; width: 100%; z-index: 200;
    background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.7) 70%, transparent 100%);
    padding: 60px 20px 24px; transform: translateY(100%); transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1);
    pointer-events: none;
  }}
  #county-panel.open {{ transform: translateY(0); }}
  #county-panel > * {{ pointer-events: auto; }}
  #county-panel .close {{
    position: absolute; top: 16px; right: 16px; width: 32px; height: 32px;
    background: rgba(255,255,255,0.1); border: none; border-radius: 50%;
    color: #fff; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center;
  }}
  #county-panel .content {{ max-width: 800px; margin: 0 auto; }}
  #county-panel h2 {{ color: #FFD700; font-size: 22px; margin-bottom: 2px; }}
  #county-panel .capital {{ color: rgba(255,255,255,0.4); font-size: 13px; }}
  #county-panel .stats {{ display: flex; gap: 20px; margin: 10px 0; flex-wrap: wrap; }}
  #county-panel .stat {{ }}
  #county-panel .stat .val {{ color: #fff; font-size: 15px; font-weight: 600; }}
  #county-panel .stat .lbl {{ color: rgba(255,255,255,0.35); font-size: 10px; text-transform: uppercase; }}
  #county-panel .sectors {{ display: flex; gap: 6px; flex-wrap: wrap; margin: 8px 0; }}
  #county-panel .sectors span {{
    background: rgba(255,215,0,0.12); color: #FFD700; padding: 3px 10px;
    border-radius: 12px; font-size: 11px;
  }}
  #county-panel .photo-grid {{
    display: flex; gap: 6px; overflow-x: auto; padding: 8px 0; margin-top: 6px;
    scrollbar-width: none;
  }}
  #county-panel .photo-grid img {{ height: 72px; border-radius: 6px; flex-shrink: 0; }}
  #county-panel .photo-grid::-webkit-scrollbar {{ display: none; }}
  #county-panel .photo-main {{
    width: 100%; max-height: 180px; object-fit: cover; border-radius: 8px; margin-top: 6px;
  }}
</style>
</head>
<body>

<div id="loading">
  <div class="spinner"></div>
  <h1>KENYA NATIONAL EXHIBITION</h1>
  <p>Loading 47 counties 3D map...</p>
</div>

<div id="header">
  <div>
    <h1>🇰🇪 KICC National Exhibition</h1>
    <div class="sub">47 Counties · Interactive 3D Map</div>
  </div>
</div>

<div id="legend">
  <h3>Scene Type</h3>
  <div class="item"><span class="dot" style="background:#8a6a4a"></span> Urban</div>
  <div class="item"><span class="dot" style="background:#5a8f4a"></span> Rural</div>
  <div class="item"><span class="dot" style="background:#3a9ac4"></span> Coastal</div>
  <div class="item"><span class="dot" style="background:#6a8f3a"></span> Mountain</div>
  <div class="item"><span class="dot" style="background:#b8904a"></span> Arid</div>
</div>

<div id="controls-hint">🖱 Drag to orbit · Scroll to zoom · Click a county</div>

<div id="county-panel">
  <button class="close" onclick="closePanel()">✕</button>
  <div class="content" id="panel-content"></div>
</div>

<script type="importmap">
{{
  "imports": {{
    "three": "https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.js",
    "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/"
  }}
}}
</script>

<script type="module">
import * as THREE from 'three';
import {{ OrbitControls }} from 'three/addons/controls/OrbitControls.js';
import {{ CSS2DRenderer, CSS2DObject }} from 'three/addons/renderers/CSS2DRenderer.js';

const COUNTIES = {counties_json};

const scene = new THREE.Scene();
scene.background = new THREE.Color(0x0a0a12);
scene.fog = new THREE.Fog(0x0a0a12, 12, 22);

const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 50);
camera.position.set(0, 8, 14);
camera.lookAt(0, 0, 0);

const renderer = new THREE.WebGLRenderer({{ antialias: true, alpha: false }});
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
renderer.shadowMap.enabled = true;
renderer.shadowMap.type = THREE.PCFSoftShadowMap;
renderer.toneMapping = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 1.2;
document.body.prepend(renderer.domElement);

const labelRenderer = new CSS2DRenderer();
labelRenderer.setSize(window.innerWidth, window.innerHeight);
labelRenderer.domElement.style.position = 'absolute';
labelRenderer.domElement.style.top = '0';
labelRenderer.domElement.style.pointerEvents = 'none';
document.body.appendChild(labelRenderer.domElement);

const controls = new OrbitControls(camera, renderer.domElement);
controls.target.set(0, 0, 0);
controls.enableDamping = true;
controls.dampingFactor = 0.08;
controls.minDistance = 4;
controls.maxDistance = 28;
controls.maxPolarAngle = Math.PI / 2.4;
controls.update();

// Lights — photorealistic setup
const ambient = new THREE.AmbientLight(0x223355, 0.5);
scene.add(ambient);

const hemi = new THREE.HemisphereLight(0x87ceeb, 0x3a2a1a, 0.6);
scene.add(hemi);

const sun = new THREE.DirectionalLight(0xffeedd, 2.0);
sun.position.set(-5, 12, 8);
sun.castShadow = true;
sun.shadow.mapSize.width = 1024;
sun.shadow.mapSize.height = 1024;
const d = 15;
sun.shadow.camera.left = -d;
sun.shadow.camera.right = d;
sun.shadow.camera.top = d;
sun.shadow.camera.bottom = -d;
sun.shadow.camera.near = 1;
sun.shadow.camera.far = 25;
scene.add(sun);

const fill = new THREE.DirectionalLight(0x4488cc, 0.4);
fill.position.set(4, 2, -6);
scene.add(fill);

const rim = new THREE.DirectionalLight(0xff8844, 0.3);
rim.position.set(-3, 1, -8);
scene.add(rim);

// Ground plane
const groundGeo = new THREE.PlaneGeometry(30, 30);
const groundMat = new THREE.MeshStandardMaterial({{
  color: 0x111118, roughness: 0.9, metalness: 0.0,
}});
const ground = new THREE.Mesh(groundGeo, groundMat);
ground.rotation.x = -Math.PI / 2;
ground.position.y = -0.05;
ground.receiveShadow = true;
scene.add(ground);

// Star field background
const starsGeo = new THREE.BufferGeometry();
const starCount = 2000;
const starPos = new Float32Array(starCount * 3);
for (let i = 0; i < starCount * 3; i++) {{
  starPos[i] = (Math.random() - 0.5) * 60;
  if (i % 3 === 1) starPos[i] = Math.abs(starPos[i]) * 0.3 - 2;
}}
starsGeo.setAttribute('position', new THREE.BufferAttribute(starPos, 3));
const starMat = new THREE.PointsMaterial({{ color: 0xffffff, size: 0.05, transparent: true, opacity: 0.6 }});
const stars = new THREE.Points(starsGeo, starMat);
scene.add(stars);

// County markers
const countyObjects = [];
const countyMeshes = [];
const raycaster = new THREE.Raycaster();
const mouse = new THREE.Vector2();

COUNTIES.forEach((c, i) => {{
  const px = c.position.x;
  const pz = c.position.z;
  const py = c.position.y;

  // Glow ring under county
  const ringGeo = new THREE.RingGeometry(0.25, 0.45, 24);
  const ringMat = new THREE.MeshBasicMaterial({{
    color: c.colors.scene, transparent: true, opacity: 0.4, side: THREE.DoubleSide,
    depthWrite: false,
  }});
  const ring = new THREE.Mesh(ringGeo, ringMat);
  ring.rotation.x = -Math.PI / 2;
  ring.position.set(px, 0.01, pz);
  scene.add(ring);

  // Cylinder marker
  const height = 0.08 + (c.brightness || 0.5) * 0.3;
  const cylGeo = new THREE.CylinderGeometry(0.35, 0.55, height, 8);
  const cylMat = new THREE.MeshStandardMaterial({{
    color: c.colors.scene, roughness: 0.4, metalness: 0.3,
    emissive: c.colors.scene, emissiveIntensity: 0.1,
  }});
  const cyl = new THREE.Mesh(cylGeo, cylMat);
  cyl.position.set(px, height / 2, pz);
  cyl.castShadow = true;
  cyl.userData = {{ countyId: c.id, countyIdx: i }};
  scene.add(cyl);
  countyMeshes.push(cyl);
  countyObjects.push(cyl);

  // Photo billboard or 3D terrain mesh
  if (c.photo_b64) {{
    if (c.terrain && c.terrain.vertices) {{
      // 3D terrain mesh from image heightfield
      const t = c.terrain;
      const geo = new THREE.BufferGeometry();
      geo.setAttribute('position', new THREE.Float32BufferAttribute(t.vertices, 3));
      geo.setIndex(t.indices);
      geo.setAttribute('uv', new THREE.Float32BufferAttribute(t.uvs, 2));
      geo.computeVertexNormals();
      const img = new Image();
      img.src = c.photo_b64;
      const tex = new THREE.Texture(img);
      tex.colorSpace = THREE.SRGBColorSpace;
      img.onload = () => {{ tex.needsUpdate = true; }};
      const mat = new THREE.MeshStandardMaterial({{
        map: tex, roughness: 0.6, metalness: 0.02,
        side: THREE.DoubleSide, flatShading: false,
      }});
      const mesh = new THREE.Mesh(geo, mat);
      const terrainScale = 1.6;
      mesh.scale.set(terrainScale, 1, terrainScale);
      const baseY = 0.5;
      mesh.position.set(px, baseY, pz);
      mesh.castShadow = true;
      mesh.receiveShadow = true;
      mesh.userData = {{ countyId: c.id, countyIdx: i, isBillboard: true, baseY: baseY }};
      scene.add(mesh);
      countyObjects.push(mesh);
    }} else {{
      // Flat billboard fallback
      const img = new Image();
      img.crossOrigin = 'anonymous';
      img.src = c.photo_b64;
      img.onload = () => {{
        const aspect = img.width / img.height;
        const bw = 1.6;
        const bh = bw / aspect;
        const tex = new THREE.Texture(img);
        tex.colorSpace = THREE.SRGBColorSpace;
        const mat = new THREE.MeshBasicMaterial({{
          map: tex, side: THREE.DoubleSide, transparent: true, opacity: 0.92,
          depthWrite: false,
        }});
        const geo = new THREE.PlaneGeometry(bw, bh);
        const baseY = 0.5 + bh / 2;
        const billboard = new THREE.Mesh(geo, mat);
        billboard.position.set(px, baseY, pz);
        billboard.userData = {{ countyId: c.id, countyIdx: i, isBillboard: true, baseY: baseY }};
        scene.add(billboard);
        countyObjects.push(billboard);
        const frameMat = new THREE.MeshBasicMaterial({{
          color: c.colors.scene, transparent: true, opacity: 0.15, side: THREE.BackSide,
        }});
        const frame = new THREE.Mesh(
          new THREE.PlaneGeometry(bw + 0.06, bh + 0.06), frameMat
        );
        frame.position.copy(billboard.position);
        frame.position.z += 0.01;
        scene.add(frame);
      }};
    }}
  }}

  // Label (CSS2D)
  const labelDiv = document.createElement('div');
  labelDiv.textContent = c.name;
  labelDiv.style.color = '#fff';
  labelDiv.style.fontSize = '10px';
  labelDiv.style.fontWeight = '600';
  labelDiv.style.textShadow = '0 1px 6px rgba(0,0,0,0.9)';
  labelDiv.style.background = 'rgba(0,0,0,0.5)';
  labelDiv.style.padding = '2px 8px';
  labelDiv.style.borderRadius = '10px';
  labelDiv.style.border = '1px solid rgba(255,255,255,0.08)';
  labelDiv.style.backdropFilter = 'blur(4px)';
  labelDiv.style.pointerEvents = 'none';

  const label = new CSS2DObject(labelDiv);
  label.position.set(px, 0.2, pz);
  scene.add(label);

  // Region connection line (to map center)
  if (i > 0) {{
    const lineMat = new THREE.LineBasicMaterial({{
      color: 0x222244, transparent: true, opacity: 0.08,
    }});
    const lineGeo = new THREE.BufferGeometry().setFromPoints([
      new THREE.Vector3(0, 0, 0),
      new THREE.Vector3(px, 0, pz),
    ]);
    const line = new THREE.Line(lineGeo, lineMat);
    scene.add(line);
  }}
}});

// Click handler
renderer.domElement.addEventListener('click', (event) => {{
  mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
  mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;
  raycaster.setFromCamera(mouse, camera);
  const hits = raycaster.intersectObjects(countyMeshes);
  if (hits.length > 0) {{
    const idx = hits[0].object.userData.countyIdx;
    if (idx !== undefined) openCountyPanel(idx);
  }}
}});

// Touch handler
renderer.domElement.addEventListener('touchstart', (e) => {{
  if (e.changedTouches.length === 1) {{
    const t = e.changedTouches[0];
    mouse.x = (t.clientX / window.innerWidth) * 2 - 1;
    mouse.y = -(t.clientY / window.innerHeight) * 2 + 1;
    raycaster.setFromCamera(mouse, camera);
    const hits = raycaster.intersectObjects(countyMeshes);
    if (hits.length > 0) {{
      const idx = hits[0].object.userData.countyIdx;
      if (idx !== undefined) openCountyPanel(idx);
    }}
  }}
}}, {{ passive: true }});

function openCountyPanel(idx) {{
  const c = COUNTIES[idx];
  if (!c) return;
  const pop = document.getElementById('county-panel');
  const content = document.getElementById('panel-content');

  const sectorsHtml = c.sectors.map(s => `<span>${{s}}</span>`).join('');
  const sectorPhotosHtml = c.sector_photos && c.sector_photos.length
    ? c.sector_photos.map(b64 => `<img src="${{b64}}" alt="sector photo">`).join('')
    : '';
  const mainPhotoHtml = c.photo_b64 ? `<img class="photo-main" src="${{c.photo_b64}}" alt="${{c.name}}">` : '';

  content.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <h2>${{c.name}}</h2>
        <div class="capital">Capital: ${{c.capital}} · ${{c.region}} Region · ${{c.scene_type_label}}</div>
      </div>
      <div style="width:32px;height:32px;border-radius:50%;background:${{c.colors.scene}};flex-shrink:0;opacity:0.8"></div>
    </div>
    <div class="stats">
      <div class="stat"><div class="val">${{(c.population / 1000000).toFixed(2)}}M</div><div class="lbl">Population</div></div>
      <div class="stat"><div class="val">${{c.area_km2.toLocaleString()}}</div><div class="lbl">Area (km²)</div></div>
      <div class="stat"><div class="val">${{c.sectors.length}}</div><div class="lbl">Key Sectors</div></div>
    </div>
    <div class="sectors">${{sectorsHtml}}</div>
    ${{mainPhotoHtml}}
    ${{sectorPhotosHtml ? `<div class="photo-grid">${{sectorPhotosHtml}}</div>` : ''}}
  `;
  pop.classList.add('open');
}}

window.closePanel = function() {{
  document.getElementById('county-panel').classList.remove('open');
}};

// Resize
window.addEventListener('resize', () => {{
  camera.aspect = window.innerWidth / window.innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(window.innerWidth, window.innerHeight);
  labelRenderer.setSize(window.innerWidth, window.innerHeight);
}});

// Animate
function animate() {{
  requestAnimationFrame(animate);
  controls.update();

  // Gentle floating animation for billboards/terrain
  const t = Date.now() * 0.0003;
  countyObjects.forEach((obj, i) => {{
    if (obj.userData && obj.userData.isBillboard) {{
      const baseY = obj.userData.baseY || 0.5;
      obj.position.y = baseY + 0.08 * Math.sin(t + obj.userData.countyIdx * 0.3);
    }}
  }});

  renderer.render(scene, camera);
  labelRenderer.render(scene, camera);
}}

// Hide loading
setTimeout(() => {{
  const loading = document.getElementById('loading');
  loading.style.opacity = '0';
  setTimeout(() => loading.style.display = 'none', 800);
}}, 600);

// Auto-hide hint
setTimeout(() => {{
  const hint = document.getElementById('controls-hint');
  if (hint) hint.style.opacity = '0';
}}, 5000);

animate();
</script>
</body>
</html>"""


def compose_scene(analyses: list[dict] | None = None, terrain: bool = False) -> str:
    """Compose the full 47-county 3D map HTML."""
    counties_data = generate_county_data_json(analyses, terrain=terrain)
    counties_json = json.dumps(counties_data, ensure_ascii=False)
    html = _build_map_html(counties_json)

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    out_path = OUTPUT_DIR / "kenya_3d_map.html"
    out_path.write_text(html, encoding="utf-8")
    return str(out_path)


if __name__ == "__main__":
    import time
    t0 = time.time()
    path = compose_scene(terrain=True)
    size_kb = os.path.getsize(path) / 1024
    print(f"Generated: {path}")
    print(f"Size: {size_kb:.0f} KB")
    print(f"Time: {time.time() - t0:.2f}s")
