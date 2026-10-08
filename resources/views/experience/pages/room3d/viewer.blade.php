@extends('layouts.blank')

@section('title', $room3d->title . ' - 3D Viewer - KICC')

@push('styles')
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { background: #FFFFFF; overflow: hidden; font-family: system-ui, sans-serif; touch-action: none; }
    canvas { display: block; width: 100vw; height: 100vh; }
    #ui { position: fixed; z-index: 100; pointer-events: none; }
    #ui > * { pointer-events: auto; }
    #header {
        position: fixed; top: 0; left: 0; width: 100%;
        padding: 12px 16px; z-index: 100;
        background: linear-gradient(to bottom, rgba(0,0,0,0.6) 0%, transparent 100%);
    }
    #header h1 { color: #FFFFFF; font-size: 14px; font-weight: 600; text-shadow: 0 2px 8px rgba(0,0,0,0.8); }
    #header a { color: rgba(255,255,255,0.6); font-size: 13px; text-decoration: none; margin-right: 12px; }
    #header a:hover { color: #FFCD05; }
    #controls-hint {
        position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);
        color: rgba(255,255,255,0.4); font-size: 12px; z-index: 100;
        background: rgba(0,0,0,0.5); padding: 6px 16px; border-radius: 16px;
        transition: opacity 2s; text-align: center;
    }
    #mode-toggle {
        position: fixed; bottom: 80px; right: 16px; z-index: 100;
        display: flex; flex-direction: column; gap: 6px;
    }
    #mode-toggle button {
        width: 44px; height: 44px; border-radius: 50%; border: none;
        background: rgba(0,0,0,0.6); color: #FFFFFF; font-size: 18px;
        cursor: pointer; backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center;
        transition: all 0.2s;
    }
    #mode-toggle button.active { background: #FFCD05; color: #0B0B0B; }
    #mode-toggle button:hover { transform: scale(1.1); }
    #photo-strip {
        position: fixed; bottom: 0; left: 0; width: 100%;
        padding: 8px 16px 16px; z-index: 100;
        background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 100%);
        display: flex; gap: 8px; overflow-x: auto;
        scrollbar-width: none;
    }
    #photo-strip::-webkit-scrollbar { display: none; }
    #photo-strip img {
        height: 48px; border-radius: 6px; cursor: pointer;
        opacity: 0.6; transition: all 0.2s; flex-shrink: 0;
        border: 2px solid transparent;
    }
    #photo-strip img.active { opacity: 1; border-color: #FFCD05; }
    #photo-strip img:hover { opacity: 1; transform: scale(1.05); }
    #loading {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        background: #FFFFFF; z-index: 1000; color: #FFFFFF;
    }
    #loading .spinner {
        width: 40px; height: 40px; border: 3px solid rgba(255,205,5,0.15);
        border-top-color: #FFCD05; border-radius: 50%; animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
@endpush

@section('content')
<div id="loading">
    <div class="spinner"></div>
    <p style="margin-top: 16px; font-size: 14px; color: rgba(255,255,255,0.6);">Loading 3D Room...</p>
</div>

<div id="header">
    <a href="{{ route('room3d.show', $room3d) }}">&larr; Back</a>
    <span style="color: #FFFFFF; font-size: 14px; font-weight: 600;">{{ $room3d->title }}</span>
</div>

<div id="mode-toggle">
    <button id="btn-orbit" class="active" title="Orbit Mode"></button>
    <button id="btn-gyro" title="Gyroscope Mode"></button>
    <button id="btn-vr" title="VR Mode"></button>
</div>

<div id="controls-hint">Drag to look around &middot; Pinch to zoom &middot; Tap photos to switch</div>

<div id="photo-strip"></div>

<script type="importmap">
{
    "imports": {
        "three": "https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.js",
        "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/"
    }
}
</script>

<script type="module">
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

const images = @json(array_map(fn($p) => url('storage/' . $p), $room3d->images()));

const scene = new THREE.Scene();
scene.background = new THREE.Color(0x111118);

const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
camera.position.set(0, 0, 0.1);

const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
document.body.appendChild(renderer.domElement);

const controls = new OrbitControls(camera, renderer.domElement);
controls.enableZoom = true;
controls.zoomSpeed = 1.0;
controls.rotateSpeed = 0.8;
controls.enablePan = false;
controls.minDistance = 0.1;
controls.maxDistance = 2.0;
controls.target.set(0, 0, 0);

let currentMode = 'orbit';
let currentPhotoIndex = 0;
let photoMeshes = [];

function createPhotoSphere(imageUrl) {
    return new Promise((resolve) => {
        const loader = new THREE.TextureLoader();
        loader.load(imageUrl, (texture) => {
            const geometry = new THREE.SphereGeometry(50, 64, 64);
            const material = new THREE.MeshBasicMaterial({
                map: texture,
                side: THREE.BackSide,
            });
            const mesh = new THREE.Mesh(geometry, material);
            resolve(mesh);
        });
    });
}

function createPhotoPlane(imageUrl, index, total) {
    return new Promise((resolve) => {
        const loader = new THREE.TextureLoader();
        loader.load(imageUrl, (texture) => {
            const aspect = texture.image.width / texture.image.height;
            const height = 3;
            const width = height * aspect;
            const geometry = new THREE.PlaneGeometry(width, height);
            const material = new THREE.MeshBasicMaterial({
                map: texture,
                side: THREE.DoubleSide,
            });
            const mesh = new THREE.Mesh(geometry, material);

            const angle = (index / total) * Math.PI * 2;
            const radius = 4;
            mesh.position.set(
                Math.sin(angle) * radius,
                0,
                Math.cos(angle) * radius
            );
            mesh.lookAt(0, 0, 0);
            resolve(mesh);
        });
    });
}

async function buildScene() {
    const strip = document.getElementById('photo-strip');

    if (images.length === 0) {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('controls-hint').textContent = 'No photos available';
        return;
    }

    if (images.length >= 4) {
        for (let i = 0; i < images.length; i++) {
            const mesh = await createPhotoSphere(images[i]);
            mesh.visible = (i === 0);
            scene.add(mesh);
            photoMeshes.push(mesh);
        }
    } else {
        for (let i = 0; i < images.length; i++) {
            const mesh = await createPhotoPlane(images[i], i, images.length);
            scene.add(mesh);
            photoMeshes.push(mesh);
        }
    }

    images.forEach((url, i) => {
        const img = document.createElement('img');
        img.src = url;
        img.className = i === 0 ? 'active' : '';
        img.addEventListener('click', () => switchPhoto(i));
        strip.appendChild(img);
    });

    document.getElementById('loading').style.display = 'none';
}

function switchPhoto(index) {
    if (index === currentPhotoIndex) return;
    
    if (photoMeshes[currentPhotoIndex]) {
        photoMeshes[currentPhotoIndex].visible = false;
    }
    if (photoMeshes[index]) {
        photoMeshes[index].visible = true;
    }
    
    document.querySelectorAll('#photo-strip img').forEach((img, i) => {
        img.className = i === index ? 'active' : '';
    });
    
    currentPhotoIndex = index;
}

// Gyroscope controls
let gyroActive = false;
let gyroAlpha = 0, gyroBeta = 0, gyroGamma = 0;

function enableGyroscope() {
    if (typeof DeviceOrientationEvent !== 'undefined') {
        if (typeof DeviceOrientationEvent.requestPermission === 'function') {
            DeviceOrientationEvent.requestPermission().then(state => {
                if (state === 'granted') startGyro();
            });
        } else {
            startGyro();
        }
    }
}

function startGyro() {
    window.addEventListener('deviceorientation', (event) => {
        if (!gyroActive) return;
        gyroAlpha = event.alpha || 0;
        gyroBeta = event.beta || 0;
        gyroGamma = event.gamma || 0;

        const rotY = (gyroAlpha / 180) * Math.PI;
        const rotX = (gyroBeta / 180) * Math.PI;
        const rotZ = (gyroGamma / 180) * Math.PI;

        camera.rotation.order = 'YXZ';
        camera.rotation.x = Math.max(-Math.PI/2, Math.min(Math.PI/2, rotX));
        camera.rotation.y = rotY;
        camera.rotation.z = 0;
    }, true);
}

// Mode switching
document.getElementById('btn-orbit').addEventListener('click', () => {
    gyroActive = false;
    currentMode = 'orbit';
    controls.enabled = true;
    document.querySelectorAll('#mode-toggle button').forEach(b => b.classList.remove('active'));
    document.getElementById('btn-orbit').classList.add('active');
    document.getElementById('controls-hint').textContent = 'Drag to look around \u00B7 Pinch to zoom';
});

document.getElementById('btn-gyro').addEventListener('click', () => {
    gyroActive = true;
    currentMode = 'gyro';
    controls.enabled = false;
    document.querySelectorAll('#mode-toggle button').forEach(b => b.classList.remove('active'));
    document.getElementById('btn-gyro').classList.add('active');
    document.getElementById('controls-hint').textContent = 'Tilt phone to look around \u00B7 Tap photos to switch';
    enableGyroscope();
});

document.getElementById('btn-vr').addEventListener('click', () => {
    if (navigator.xr) {
        navigator.xr.isSessionSupported('immersive-vr').then(supported => {
            if (supported) {
                document.getElementById('controls-hint').textContent = 'Entering VR mode...';
            } else {
                document.getElementById('controls-hint').textContent = 'VR not supported on this device';
            }
        });
    } else {
        document.getElementById('controls-hint').textContent = 'WebXR not available';
    }
});

// Resize
window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
});

// Animate
function animate() {
    requestAnimationFrame(animate);
    if (currentMode === 'orbit') {
        controls.update();
    }
    renderer.render(scene, camera);
}

buildScene().then(() => {
    animate();
    setTimeout(() => {
        const hint = document.getElementById('controls-hint');
        if (hint) hint.style.opacity = '0';
    }, 5000);
});
</script>
@endsection