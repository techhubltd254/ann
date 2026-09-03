@props([
    'boothId' => 'booth-3d-' . uniqid(),
    'width' => '100%',
    'height' => '400px',
    'backgroundColor' => '#0B1E57',
])

<div class="relative overflow-hidden rounded-xl" style="width: {{ $width }}; height: {{ $height }}; background: {{ $backgroundColor }}"
     x-data="booth3d('{{ $boothId }}')"
     x-init="init()">
    <canvas x-ref="canvas" id="{{ $boothId }}" class="w-full h-full" style="display:block"></canvas>

    <div class="absolute bottom-3 left-3 z-10 flex items-center gap-1.5">
        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-black/50 backdrop-blur text-white/80">3D Virtual Booth</span>
    </div>

    <div class="absolute bottom-3 right-3 z-10 flex items-center gap-1.5">
        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-black/50 backdrop-blur text-white/80">WASD + Drag to navigate</span>
    </div>
</div>

@push('scripts')
<script>
function booth3d(containerId) {
    return {
        scene: null,
        camera: null,
        renderer: null,
        controls: null,
        animationId: null,

        init() {
            // Load Three.js dynamically
            Promise.all([
                this.loadScript('https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js'),
            ]).then(() => {
                this.initScene();
            }).catch(() => {});
        },

        loadScript(url) {
            return new Promise((resolve, reject) => {
                var script = document.createElement('script');
                script.src = url;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        },

        initScene() {
            var THREE = window.THREE;
            if (!THREE || !this.$refs.canvas) return;

            var container = this.$refs.canvas.parentElement;
            var width = container.clientWidth;
            var height = container.clientHeight;

            this.scene = new THREE.Scene();
            this.scene.background = new THREE.Color(0x0B1E57);

            this.camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 100);
            this.camera.position.set(3, 2, 5);
            this.camera.lookAt(0, 0, 0);

            this.renderer = new THREE.WebGLRenderer({
                canvas: this.$refs.canvas,
                antialias: true,
                alpha: true
            });
            this.renderer.setSize(width, height);
            this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            this.renderer.shadowMap.enabled = true;

            // Lights
            var ambient = new THREE.AmbientLight(0x404060, 0.8);
            this.scene.add(ambient);
            var dirLight = new THREE.DirectionalLight(0xffffff, 1.2);
            dirLight.position.set(5, 10, 7);
            dirLight.castShadow = true;
            this.scene.add(dirLight);
            var fillLight = new THREE.DirectionalLight(0x4488ff, 0.4);
            fillLight.position.set(-5, 0, 5);
            this.scene.add(fillLight);

            // Floor (neon grid)
            var gridHelper = new THREE.GridHelper(8, 12, 0x4488ff, 0x224488);
            gridHelper.position.y = -1;
            this.scene.add(gridHelper);

            // Floor plane
            var floorGeo = new THREE.PlaneGeometry(8, 8);
            var floorMat = new THREE.MeshStandardMaterial({
                color: 0x0a1a3a,
                transparent: true,
                opacity: 0.6,
                side: THREE.DoubleSide
            });
            var floor = new THREE.Mesh(floorGeo, floorMat);
            floor.rotation.x = -Math.PI / 2;
            floor.position.y = -1;
            floor.receiveShadow = true;
            this.scene.add(floor);

            // Back wall
            var wallMat = new THREE.MeshStandardMaterial({
                color: 0x1a2a5a,
                transparent: true,
                opacity: 0.5,
                side: THREE.DoubleSide
            });
            var backWall = new THREE.Mesh(new THREE.PlaneGeometry(6, 4), wallMat);
            backWall.position.set(0, 1, -3);
            this.scene.add(backWall);

            // Pedestal (product display)
            var pedestalMat = new THREE.MeshStandardMaterial({ color: 0x4488ff, metalness: 0.3, roughness: 0.4 });
            var pedestal = new THREE.Mesh(new THREE.CylinderGeometry(0.4, 0.5, 0.1, 16), pedestalMat);
            pedestal.position.set(0, -0.95, 0);
            this.scene.add(pedestal);

            // Product sphere
            var productMat = new THREE.MeshStandardMaterial({
                color: 0xFFCD05,
                metalness: 0.7,
                roughness: 0.2,
                emissive: 0xFFCD05,
                emissiveIntensity: 0.1
            });
            var product = new THREE.Mesh(new THREE.SphereGeometry(0.4, 32, 32), productMat);
            product.position.set(0, -0.3, 0);
            product.castShadow = true;
            this.scene.add(product);

            // Side pillars
            var pillarMat = new THREE.MeshStandardMaterial({
                color: 0x2a4a8a,
                metalness: 0.5,
                roughness: 0.3
            });
            for (var i = -1; i <= 1; i += 2) {
                var pillar = new THREE.Mesh(new THREE.BoxGeometry(0.15, 2.5, 0.15), pillarMat);
                pillar.position.set(i * 2.5, 0.25, -2.5);
                this.scene.add(pillar);
            }

            // Orbit-like controls via mouse drag
            var isDragging = false;
            var prevMouse = { x: 0, y: 0 };
            var targetRotation = { x: 0, y: 0 };
            var currentRotation = { x: 0, y: 0 };

            container.addEventListener('mousedown', (e) => {
                isDragging = true;
                prevMouse = { x: e.clientX, y: e.clientY };
            });
            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                var dx = e.clientX - prevMouse.x;
                var dy = e.clientY - prevMouse.y;
                targetRotation.y += dx * 0.01;
                targetRotation.x = Math.max(-1, Math.min(1, targetRotation.x + dy * 0.01));
                prevMouse = { x: e.clientX, y: e.clientY };
            });
            document.addEventListener('mouseup', () => { isDragging = false; });

            // Keyboard controls
            var keys = {};
            document.addEventListener('keydown', (e) => { keys[e.key.toLowerCase()] = true; });
            document.addEventListener('keyup', (e) => { keys[e.key.toLowerCase()] = false; });

            // Animation loop
            var animate = () => {
                this.animationId = requestAnimationFrame(animate);

                // Smooth rotation
                currentRotation.x += (targetRotation.x - currentRotation.x) * 0.08;
                currentRotation.y += (targetRotation.y - currentRotation.y) * 0.08;

                // Keyboard movement
                var speed = 0.03;
                var move = { x: 0, z: 0 };
                if (keys['w'] || keys['arrowup']) move.z -= speed;
                if (keys['s'] || keys['arrowdown']) move.z += speed;
                if (keys['a'] || keys['arrowleft']) move.x -= speed;
                if (keys['d'] || keys['arrowright']) move.x += speed;

                // Rotate product
                product.rotation.y += 0.01;

                // Update camera orbit
                var radius = 6;
                var camX = Math.sin(currentRotation.y) * radius * Math.cos(currentRotation.x);
                var camY = Math.sin(currentRotation.x) * radius + 1;
                var camZ = Math.cos(currentRotation.y) * radius * Math.cos(currentRotation.x);
                this.camera.position.set(camX + move.x, camY, camZ + move.z);
                this.camera.lookAt(move.x, 0, move.z);

                this.renderer.render(this.scene, this.camera);
            };
            animate();
        },

        destroy() {
            if (this.animationId) cancelAnimationFrame(this.animationId);
            if (this.renderer) this.renderer.dispose();
        }
    };
}
</script>
@endpush