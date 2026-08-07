"""Convert a 2D image to a 3D terrain mesh using brightness-based heightfield.

Output: JSON-serializable dict with vertices, indices, uvs, and base64 texture.
These can be embedded directly into Three.js as BufferGeometry.
"""

from __future__ import annotations

import base64
import json
from io import BytesIO
from pathlib import Path

from PIL import Image

GRID_SIZE = 32
MAX_HEIGHT = 0.3


def _image_to_base64(img: Image.Image, max_size: int = 512) -> str:
    if max(img.size) > max_size:
        ratio = max_size / max(img.size)
        img = img.resize((int(img.width * ratio), int(img.height * ratio)), Image.LANCZOS)
    buf = BytesIO()
    img.save(buf, format="JPEG", quality=80)
    return f"data:image/jpeg;base64,{base64.b64encode(buf.getvalue()).decode()}"


def image_to_3d_terrain(
    image_path: str,
    grid_size: int = GRID_SIZE,
    max_height: float = MAX_HEIGHT,
    invert: bool = False,
    edge_boost: float = 0.0,
) -> dict:
    """Convert an image to a 3D terrain mesh.

    Args:
        image_path: Path to the image.
        grid_size: Number of grid subdivisions (N×N vertices).
        max_height: Maximum height displacement in 3D units.
        invert: If True, brighter = lower (for sky-up images).
        edge_boost: 0-1, exaggerates height differences for sharper features.

    Returns:
        Dict with keys: vertices, indices, uvs, texture (base64 data URL),
        width, height, grid_size.
    """
    img = Image.open(image_path).convert("RGB")
    w, h = img.size

    # Subsample image to grid
    sampled = img.resize((grid_size, grid_size), Image.LANCZOS)
    pixels = list(sampled.getdata())

    # Compute heights from luminance
    heights = []
    for r, g, b in pixels:
        lum = (0.299 * r + 0.587 * g + 0.114 * b) / 255.0
        if invert:
            lum = 1.0 - lum
        h_val = lum * max_height

        if edge_boost > 0:
            h_val = h_val ** (1.0 - edge_boost * 0.5)

        heights.append(h_val)

    # Generate vertices, UVs, indices
    vertices = []
    uvs = []
    indices = []

    scale_x = 1.0
    scale_z = 1.0

    # Maintain aspect ratio
    aspect = w / h
    if aspect >= 1.0:
        scale_x = 1.0
        scale_z = 1.0 / aspect
    else:
        scale_x = aspect
        scale_z = 1.0

    for iy in range(grid_size):
        for ix in range(grid_size):
            idx = iy * grid_size + ix
            x = (ix / (grid_size - 1) - 0.5) * scale_x
            z = (iy / (grid_size - 1) - 0.5) * scale_z
            y = heights[idx]
            vertices.extend([round(x, 4), round(y, 4), round(z, 4)])
            uvs.extend([ix / (grid_size - 1), 1.0 - iy / (grid_size - 1)])

    for iy in range(grid_size - 1):
        for ix in range(grid_size - 1):
            a = iy * grid_size + ix
            b = a + 1
            c = (iy + 1) * grid_size + ix
            d = c + 1
            indices.extend([a, c, b, b, c, d])

    texture_b64 = _image_to_base64(img)

    return {
        "vertices": vertices,
        "indices": indices,
        "uvs": uvs,
        "texture": texture_b64,
        "width": w,
        "height": h,
        "grid_size": grid_size,
    }


def terrain_to_threejs_code(terrain: dict) -> str:
    """Generate Three.js JavaScript code from terrain data."""
    return f"""const geo = new THREE.BufferGeometry();
geo.setAttribute('position', new THREE.Float32BufferAttribute({json.dumps(terrain['vertices'])}, 3));
geo.setIndex({json.dumps(terrain['indices'])});
geo.setAttribute('uv', new THREE.Float32BufferAttribute({json.dumps(terrain['uvs'])}, 2));
geo.computeVertexNormals();
const tex = new THREE.Texture(new Image());
tex.colorSpace = THREE.SRGBColorSpace;
tex.image.src = '{terrain['texture']}';
tex.image.onload = () => {{ tex.needsUpdate = true; }};
const mat = new THREE.MeshStandardMaterial({{
  map: tex,
  roughness: 0.6,
  metalness: 0.05,
  side: THREE.DoubleSide,
  flatShading: true,
}});
const mesh = new THREE.Mesh(geo, mat);
mesh.castShadow = true;
mesh.receiveShadow = true;
"""


def estimate_size_bytes(terrain: dict) -> int:
    """Estimate the JSON payload size for the terrain."""
    raw = json.dumps(terrain)
    return len(raw.encode())


if __name__ == "__main__":
    import sys
    path = sys.argv[1] if len(sys.argv) > 1 else "/home/kicc/Desktop/kicc/county profile pics labeled/Nairobi.jpg"
    t = image_to_3d_terrain(path, grid_size=32)
    print(f"Vertices: {len(t['vertices']) // 3}")
    print(f"Triangles: {len(t['indices']) // 3}")
    print(f"Payload: {estimate_size_bytes(t) // 1024} KB")
    print(f"Texture: {len(t['texture']) // 1024} KB inline")
