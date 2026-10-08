@extends('layouts.app')
@section('title', 'Virtual Tour — KICC')
@section('content')
<div class="pt-20 max-w-6xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-gray-900 mb-2">Virtual Tour of KICC</h1>
    <p class="text-gray-500 text-sm mb-6">Explore KICC's venues through our 360° virtual experience.</p>
    <div class="bg-black rounded-2xl overflow-hidden aspect-video flex items-center justify-center">
        <div id="three-container" style="width:100%;height:500px;"></div>
    </div>
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-3 mt-6" id="tour-selector">
        @foreach(['tsavo-hall','amphitheatre','aberdares','lenana-hills','courtyard','lawn'] as $slug)
        <button class="tour-btn px-3 py-2 rounded-xl border border-gray-200 text-xs font-bold hover:bg-[#0B0B0B] hover:text-white transition-all" data-slug="{{ $slug }}">{{ ucfirst(str_replace('-',' ',$slug)) }}</button>
        @endforeach
    </div>
</div>
<script type="importmap">{"imports":{"three":"https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.js","three/addons/":"https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/"}}</script>
<script type="module">
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
const container = document.getElementById('three-container');
const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(75, container.clientWidth/container.clientHeight, 0.1, 1000);
const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setSize(container.clientWidth, container.clientHeight);
container.appendChild(renderer.domElement);
const controls = new OrbitControls(camera, renderer.domElement);
controls.enableZoom = true; controls.enablePan = false; controls.rotateSpeed = 0.8;
camera.position.set(0, 0, 0.1);
const sphere = new THREE.Mesh(new THREE.SphereGeometry(500, 60, 40), new THREE.MeshBasicMaterial({ side: THREE.BackSide }));
scene.add(sphere);
function loadPanorama(slug) {
    const loader = new THREE.TextureLoader();
    loader.load(`/storage/kicc/venues/${slug}.jpg`, (tex) => { sphere.material.map = tex; sphere.material.needsUpdate = true; });
}
loadPanorama('mainfront');
document.querySelectorAll('.tour-btn').forEach(b => b.addEventListener('click', () => loadPanorama(b.dataset.slug)));
function onResize() { camera.aspect = container.clientWidth/container.clientHeight; camera.updateProjectionMatrix(); renderer.setSize(container.clientWidth, container.clientHeight); }
window.addEventListener('resize', onResize);
(function loop() { requestAnimationFrame(loop); controls.update(); renderer.render(scene, camera); })();
</script>
@endsection