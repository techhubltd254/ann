#!/usr/bin/env node
/**
 * render_splat.js v3 - Headless WebGL1 Gaussian Splat Renderer
 * Uses stack-gl (headless WebGL) for GPU-accelerated rendering
 *
 * Usage: node render_splat.cjs --splat scene.splat --trajectory path.json --output out.mp4
 */
const createContext = require('gl');
const { createCanvas } = require('canvas');
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

// Parse args
const args = {};
process.argv.slice(2).forEach((a, i) => {
    if (a.startsWith('--')) args[a.slice(2)] = process.argv.slice(2)[i+1];
});

const splatFile = args.splat || (() => { throw '--splat required'; })();
const trajFile = args.trajectory || (() => { throw '--trajectory required'; })();
const output = args.output || 'output.mp4';
const fps = parseInt(args.fps) || 30;
const width = parseInt(args.width) || 1920;
const height = parseInt(args.height) || 1080;

// Create headless WebGL context
const gl = createContext(width, height);

// ====== WebGL1-Compatible Shaders ======
const vertexSrc = `
    attribute vec3 position;
    attribute vec4 color;
    uniform mat4 viewProj;
    varying vec4 vColor;
    void main() {
        vec4 v = viewProj * vec4(position, 1.0);
        gl_Position = v;
        gl_PointSize = 200.0;
        vColor = color;
    }
`;

const fragmentSrc = `
    precision highp float;
    varying vec4 vColor;
    void main() {
        gl_FragColor = vColor;
    }
`;

// Compile shaders (same as WebGL1)

// Compile shaders
function compileShader(src, type) {
    const s = gl.createShader(type);
    gl.shaderSource(s, src);
    gl.compileShader(s);
    if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) {
        console.error('Shader compile error:', gl.getShaderInfoLog(s));
        process.exit(1);
    }
    return s;
}

const program = gl.createProgram();
gl.attachShader(program, compileShader(vertexSrc, gl.VERTEX_SHADER));
gl.attachShader(program, compileShader(fragmentSrc, gl.FRAGMENT_SHADER));

// Bind attribute locations BEFORE linking
gl.bindAttribLocation(program, 0, 'position');
gl.bindAttribLocation(program, 1, 'color');

gl.linkProgram(program);
if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
    console.error('Program link error:', gl.getProgramInfoLog(program));
    process.exit(1);
}
gl.useProgram(program);

const uViewProj = gl.getUniformLocation(program, 'viewProj');

// Load splat file
function loadSplat(filepath) {
    const buf = fs.readFileSync(filepath);
    const data = buf.slice(0);
    const count = Math.floor(data.byteLength / 32);
    
    // Store raw position data for per-frame manipulation
    const positions = new Float32Array(count * 3);
    const colors = new Uint8Array(count * 4);
    const dv = new DataView(data.buffer, data.byteOffset, data.byteLength);
    for (let i = 0; i < count; i++) {
        const off = i * 32;
        positions[i*3] = dv.getFloat32(off, true);
        positions[i*3+1] = dv.getFloat32(off+4, true);
        positions[i*3+2] = dv.getFloat32(off+8, true);
        colors[i*4] = dv.getUint8(off+12);
        colors[i*4+1] = dv.getUint8(off+13);
        colors[i*4+2] = dv.getUint8(off+14);
        colors[i*4+3] = dv.getUint8(off+15);
    }
    
    // VBO for positions only (3 floats, updated each frame)
    const vbo = gl.createBuffer();
    
    return { count, vbo, positions, colors, dv };
}

// Read pixels from WebGL context
function readPixels() {
    const pixels = new Uint8Array(width * height * 4);
    gl.readPixels(0, 0, width, height, gl.RGBA, gl.UNSIGNED_BYTE, pixels);
    
    // WebGL reads bottom-up, flip vertically
    const canvas = createCanvas(width, height);
    const ctx = canvas.getContext('2d');
    const imageData = ctx.createImageData(width, height);
    const flipped = new Uint8Array(width * height * 4);
    
    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            const srcIdx = (y * width + x) * 4;
            const dstIdx = ((height - 1 - y) * width + x) * 4;
            flipped[dstIdx] = pixels[srcIdx];
            flipped[dstIdx+1] = pixels[srcIdx+1];
            flipped[dstIdx+2] = pixels[srcIdx+2];
            flipped[dstIdx+3] = pixels[srcIdx+3];
        }
    }
    
    imageData.data.set(flipped);
    ctx.putImageData(imageData, 0, 0);
    return canvas.toBuffer('image/png');
}

function renderFrame(splatData, theta, phi, distance) {
    gl.viewport(0, 0, width, height);
    gl.clearColor(0, 0, 0, 1);
    gl.clear(gl.COLOR_BUFFER_BIT);
    gl.enable(gl.BLEND);
    gl.blendFunc(gl.SRC_ALPHA, gl.ONE_MINUS_SRC_ALPHA);
    gl.disable(gl.DEPTH_TEST);
    
    // Build per-frame transformed positions
    const { count, positions, colors } = splatData;
    
    // Create interleaved buffer: position (3 float32) + color (4 uint8) = 16 bytes per point
    const bufSize = count * 16;
    const buf = Buffer.alloc(bufSize);
    const f32 = new Float32Array(buf.buffer, buf.byteOffset, bufSize/4);
    const u8 = new Uint8Array(buf.buffer, buf.byteOffset, bufSize);
    
    // Rotation matrix from theta, phi
    const ct = Math.cos(theta), st = Math.sin(theta);
    const cp = Math.cos(phi), sp = Math.sin(phi);
    // Rotate around Y then X (simplified orbit camera)
    
    for (let i = 0; i < count; i++) {
        const i3 = i * 3, i4 = i * 4, i16 = i * 16;
        
        // Original position
        let px = positions[i3];
        let py = positions[i3+1];
        let pz = positions[i3+2];
        
        // Simple camera orbit: rotate around Y axis by theta, then around X by phi
        // Then translate by distance along Z
        // First, rotate around Y
        let rx = px * ct - pz * st;
        let rz = px * st + pz * ct;
        let ry = py;
        
        // Then rotate around X (phi)
        let ry2 = ry * cp - rz * sp;
        let rz2 = ry * sp + rz * cp;
        let rx2 = rx;
        
        // Scale by 1/distance (zoom)
        let scale = 1.0 / Math.max(distance, 0.1);
        
        // Write position
        f32[i16/4] = rx2 * scale;     // offset 0
        f32[i16/4 + 1] = ry2 * scale; // offset 4
        f32[i16/4 + 2] = rz2 * scale; // offset 8
        
        // Write color  
        u8[i16 + 12] = colors[i4];
        u8[i16 + 13] = colors[i4+1];
        u8[i16 + 14] = colors[i4+2];
        u8[i16 + 15] = colors[i4+3];
    }
    
    gl.bindBuffer(gl.ARRAY_BUFFER, splatData.vbo);
    gl.bufferData(gl.ARRAY_BUFFER, buf, gl.STREAM_DRAW);
    gl.vertexAttribPointer(0, 3, gl.FLOAT, false, 16, 0);
    gl.enableVertexAttribArray(0);
    gl.vertexAttribPointer(1, 4, gl.UNSIGNED_BYTE, true, 16, 12);
    gl.enableVertexAttribArray(1);
    
    // Identity view-proj (points already in NDC)
    const id = new Float32Array([1,0,0,0, 0,1,0,0, 0,0,1,0, 0,0,0,1]);
    gl.uniformMatrix4fv(uViewProj, false, id);
    
    gl.drawArrays(gl.POINTS, 0, count);
    gl.bindBuffer(gl.ARRAY_BUFFER, null);
    
    return readPixels();
}

// Main loop
function main() {
    console.log(`Loading splat: ${splatFile}`);
    const splatData = loadSplat(splatFile);
    console.log(`Loaded ${splatData.count} Gaussians`);
    
    const trajectory = JSON.parse(fs.readFileSync(trajFile, 'utf8'));
    const kfs = trajectory.keyframes;
    if (!kfs || kfs.length < 2) throw 'Need at least 2 keyframes';
    
    const totalFrames = Math.round(kfs[kfs.length - 1].t * fps);
    console.log(`Rendering ${totalFrames} frames at ${fps}fps (${width}x${height})`);
    
    const ffmpeg = spawn('ffmpeg', [
        '-y', '-f', 'image2pipe', '-vcodec', 'png',
        '-r', String(fps), '-i', '-',
        '-vcodec', 'libx264', '-preset', 'fast', '-crf', '20',
        '-pix_fmt', 'yuv420p', '-movflags', '+faststart',
        output
    ]);
    
    let frameCount = 0;
    for (let i = 0; i < totalFrames; i++) {
        const currentTime = i / fps;
        
        let segIdx = 0;
        for (let j = 0; j < kfs.length - 1; j++) {
            if (currentTime >= kfs[j].t && currentTime <= kfs[j+1].t) {
                segIdx = j;
                break;
            }
        }
        
        const a = kfs[segIdx];
        const b = kfs[segIdx + 1];
        const segDuration = b.t - a.t;
        const segProgress = segDuration > 0 ? (currentTime - a.t) / segDuration : 0;
        const t = segProgress * segProgress * (3 - 2 * segProgress);
        
        const theta = a.theta + (b.theta - a.theta) * t;
        const phi = a.phi + (b.phi - a.phi) * t;
        const dist = a.dist + (b.dist - a.dist) * t;
        
        const png = renderFrame(splatData, theta, phi, dist);
        ffmpeg.stdin.write(png);
        
        frameCount++;
        if (frameCount % 30 === 0) {
            console.log(`  Frame ${frameCount}/${totalFrames}`);
        }
    }
    
    ffmpeg.stdin.end();
    ffmpeg.on('close', (code) => {
        console.log(`\nDone! ${code === 0 ? 'Video: ' + output : 'FFmpeg error ' + code}`);
    });
}

main();