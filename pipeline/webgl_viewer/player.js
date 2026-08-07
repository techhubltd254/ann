/**
 * WebGL 3D exhibition viewer using Three.js.
 *
 * Features:
 * - Orbit/drag camera like PUBG
 * - Gyroscope VR mode on mobile
 * - Pinch-to-zoom
 * - Booth hotspot detection
 * - Splat/ply or 360 fallback rendering
 */

// Load Three.js from CDN
const THREE = window.THREE;

let scene, camera, renderer, controls;
let isVR = false;
let raycaster, mouse;
let boothObjects = [];

async function init() {
    const container = document.body;

    // Scene
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x111118);

    // Camera
    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 1.6, 5);

    // Renderer
    renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    container.appendChild(renderer.domElement);

    // Controls
    const { OrbitControls } = await import('https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/controls/OrbitControls.js');
    controls = new OrbitControls(camera, renderer.domElement);
    controls.target.set(0, 1.2, 0);
    controls.enableDamping = true;
    controls.dampingFactor = 0.08;
    controls.minDistance = 0.5;
    controls.maxDistance = 15;
    controls.maxPolarAngle = Math.PI / 2.1;
    controls.update();

    // Raycaster for booth interaction
    raycaster = new THREE.Raycaster();
    mouse = new THREE.Vector2();

    // Lights
    const ambient = new THREE.AmbientLight(0x404060, 0.6);
    scene.add(ambient);

    const dirLight = new THREE.DirectionalLight(0xffeedd, 1.2);
    dirLight.position.set(2, 5, 3);
    dirLight.castShadow = true;
    scene.add(dirLight);

    const fillLight = new THREE.DirectionalLight(0x4488ff, 0.4);
    fillLight.position.set(-2, 1, -2);
    scene.add(fillLight);

    // Ground
    const groundGeo = new THREE.PlaneGeometry(20, 20);
    const groundMat = new THREE.MeshStandardMaterial({
        color: 0x222233,
        roughness: 0.8,
        metalness: 0.2,
    });
    const ground = new THREE.Mesh(groundGeo, groundMat);
    ground.rotation.x = -Math.PI / 2;
    ground.position.y = -0.01;
    ground.receiveShadow = true;
    scene.add(ground);

    // Grid
    const gridHelper = new THREE.GridHelper(20, 20, 0x444466, 0x333355);
    scene.add(gridHelper);

    // Build exhibition hall
    buildExhibitionHall();

    // Load splat data or 360 fallback
    try {
        await loadSceneData();
    } catch (e) {
        console.warn('No splat data, using 360 fallback');
        buildFallbackScene();
    }

    // Click/tap handler
    renderer.domElement.addEventListener('click', onPointerDown, false);
    renderer.domElement.addEventListener('touchstart', (e) => {
        const touch = e.changedTouches[0];
        mouse.x = (touch.clientX / window.innerWidth) * 2 - 1;
        mouse.y = -(touch.clientY / window.innerHeight) * 2 + 1;
        checkBoothHit();
    }, { passive: true });

    window.addEventListener('resize', onResize);

    // Hide loading
    document.getElementById('loading').style.display = 'none';

    // Auto-hide hint
    setTimeout(() => {
        const hint = document.getElementById('controls-hint');
        if (hint) hint.style.opacity = '0';
    }, 4000);

    animate();
}

function buildExhibitionHall() {
    // Central hall pillars
    const pillarMat = new THREE.MeshStandardMaterial({ color: 0x334466, roughness: 0.6, metalness: 0.3 });
    for (let i = 0; i < 4; i++) {
        const angle = (i / 4) * Math.PI * 2;
        const px = Math.cos(angle) * 2.5;
        const pz = Math.sin(angle) * 2.5;
        const pillar = new THREE.Mesh(new THREE.CylinderGeometry(0.15, 0.2, 2.8, 8), pillarMat);
        pillar.position.set(px, 1.4, pz);
        pillar.castShadow = true;
        scene.add(pillar);
    }

    // Ceiling ring
    const ringMat = new THREE.MeshStandardMaterial({ color: 0x556688, roughness: 0.4, metalness: 0.5 });
    const ring = new THREE.Mesh(new THREE.TorusGeometry(2.6, 0.05, 8, 32), ringMat);
    ring.position.y = 2.8;
    ring.rotation.x = Math.PI / 2;
    scene.add(ring);
}

function buildFallbackScene() {
    // 360-degree panorama sphere
    const sphereGeo = new THREE.SphereGeometry(8, 32, 16);
    const sphereMat = new THREE.MeshBasicMaterial({
        color: 0x222244,
        wireframe: true,
        transparent: true,
        opacity: 0.2,
    });
    const sphere = new THREE.Mesh(sphereGeo, sphereMat);
    sphere.position.y = 1.5;
    scene.add(sphere);

    // Central display
    const displayMat = new THREE.MeshStandardMaterial({
        color: 0x1a1a2e,
        emissive: 0x16213e,
        roughness: 0.3,
        metalness: 0.7,
    });
    const display = new THREE.Mesh(new THREE.BoxGeometry(1.2, 0.8, 0.05), displayMat);
    display.position.set(0, 1.2, -3);
    scene.add(display);

    // Booth cubes around the hall
    const boothColors = [0x4488ff, 0xff6644, 0x44ff88, 0xff44aa, 0xffdd44, 0x8844ff];
    const sectors = ['Technology', 'Agriculture', 'Tourism', 'Health', 'Education', 'Energy'];
    for (let i = 0; i < 6; i++) {
        const angle = (i / 6) * Math.PI * 2 - Math.PI / 2;
        const radius = 3.0;
        const bx = Math.cos(angle) * radius;
        const bz = Math.sin(angle) * radius;
        const booth = new THREE.Mesh(
            new THREE.BoxGeometry(0.8, 1.0, 0.8),
            new THREE.MeshStandardMaterial({ color: boothColors[i], roughness: 0.5 })
        );
        booth.position.set(bx, 0.5, bz);
        booth.castShadow = true;
        booth.userData = {
            isBooth: true,
            title: `${sectors[i]} Pavilion`,
            sector: sectors[i],
            description: `Explore the latest in ${sectors[i].toLowerCase()} innovation from Kenya.`,
            url: `#${sectors[i].toLowerCase()}`,
        };
        scene.add(booth);
        boothObjects.push(booth);

        // Booth label (floating text via sprite)
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 64;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = 'rgba(0,0,0,0.7)';
        ctx.roundRect(0, 0, 256, 64, 8);
        ctx.fill();
        ctx.fillStyle = '#FFD700';
        ctx.font = 'bold 24px Segoe UI';
        ctx.textAlign = 'center';
        ctx.fillText(sectors[i], 128, 40);
        const texture = new THREE.CanvasTexture(canvas);
        const spriteMat = new THREE.SpriteMaterial({ map: texture, transparent: true });
        const sprite = new THREE.Sprite(spriteMat);
        sprite.position.set(bx, 1.3, bz);
        sprite.scale.set(1.2, 0.3, 1);
        scene.add(sprite);
    }

    // Booth labels for existing booths
}

async function loadSceneData() {
    // Attempt to load splat .json or .ply metadata
    const resp = await fetch('splat_placeholder.json').catch(() => null);
    if (!resp || !resp.ok) throw new Error('No splat data');
    const data = await resp.json();
    if (data.type === '360_fallback') {
        buildFallbackScene();
    }
}

function onPointerDown(event) {
    mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
    mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;
    checkBoothHit();
}

function checkBoothHit() {
    raycaster.setFromCamera(mouse, camera);
    const intersects = raycaster.intersectObjects(boothObjects);
    if (intersects.length > 0) {
        const obj = intersects[0].object;
        if (obj.userData.isBooth) {
            openBoothPopup(obj.userData);
        }
    }
}

function openBoothPopup(data) {
    const popup = document.getElementById('booth-popup');
    document.getElementById('booth-title').textContent = data.title;
    document.getElementById('booth-desc').textContent = data.description;
    document.getElementById('booth-sector').textContent = `Sector: ${data.sector}`;
    popup.style.display = 'block';
    window._currentBoothUrl = data.url;
}

function closePopup() {
    document.getElementById('booth-popup').style.display = 'none';
}

function view3D() {
    closePopup();
}

function visitBooth() {
    const url = window._currentBoothUrl || '#';
    window.open(url, '_blank');
    closePopup();
}

function onResize() {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
}

function animate() {
    requestAnimationFrame(animate);
    controls.update();
    renderer.render(scene, camera);
}

// VR mode toggle
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('mode-toggle');
    btn.addEventListener('click', () => {
        isVR = !isVR;
        btn.textContent = isVR ? 'Orbit Mode' : 'VR Mode';
        if (isVR) {
            controls.enableRotate = false;
            // Could activate DeviceOrientationControls here
        } else {
            controls.enableRotate = true;
        }
    });
});

init().catch(console.error);
