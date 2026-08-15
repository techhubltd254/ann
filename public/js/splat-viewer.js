/**
 * 4D Gaussian Splat Holographic Viewer
 * Renders .splat files interactively in WebGL
 * Based on antimatter15/splat rendering core
 */
class SplatViewer {
    constructor(canvas) {
        this.canvas = canvas;
        this.gl = canvas.getContext('webgl2', { antialias: false });
        this.width = canvas.width;
        this.height = canvas.height;
        this.splatBuffer = null;
        this.numGaussians = 0;
        this.viewMatrix = new Float32Array(16);
        this.projMatrix = new Float32Array(16);
        this.camPos = [0, 0, -4];
        this.target = [0, 0, 0];
        this.theta = 0;
        this.phi = 0.3;
        this.distance = 4;
        this.isDragging = false;
        this.lastX = 0;
        this.lastY = 0;
        this.autoRotate = true;
        this.setupShaders();
        this.initView();
        this.setupControls();
        this.resize();
    }

    setupShaders() {
        const gl = this.gl;
        // Vertex shader for splat rendering
        const vs = gl.createShader(gl.VERTEX_SHADER);
        gl.shaderSource(vs, `#version 300 es
            precision highp float;
            in vec3 position;
            in vec4 color;
            in vec3 scale;
            in vec4 rot;
            uniform mat4 viewProj;
            uniform vec2 focal;
            uniform vec2 viewport;
            out vec4 vColor;
            out vec2 vPos;
            void main() {
                vec4 q = normalize(rot);
                float r = q.x, x = q.y, y = q.z, z = q.w;
                mat3 R = mat3(
                    1-2*(y*y+z*z), 2*(x*y-r*z), 2*(x*z+r*y),
                    2*(x*y+r*z), 1-2*(x*x+z*z), 2*(y*z-r*x),
                    2*(x*z-r*y), 2*(y*z+r*x), 1-2*(x*x+y*y)
                );
                vec3 s = scale * 0.6;
                mat3 M = mat3(s.x*R[0], s.y*R[1], s.z*R[2]);
                vec4 v = viewProj * vec4(position, 1.0);
                gl_Position = v;
                vec2 p = v.xy / v.w;
                float d = -v.z;
                mat3 C = M * transpose(M);
                vec2 conic = vec2(C[1][1], -C[1][0]) / (C[0][0]*C[1][1] - C[1][0]*C[1][0]);
                vColor = color;
                vPos = p;
            }
        `);
        gl.compileShader(vs);

        const fs = gl.createShader(gl.FRAGMENT_SHADER);
        gl.shaderSource(fs, `#version 300 es
            precision highp float;
            in vec4 vColor;
            in vec2 vPos;
            out vec4 fragColor;
            uniform vec2 viewport;
            void main() {
                vec2 d = (gl_FragCoord.xy - viewport/2.0) / viewport * 2.0 - vPos;
                float dist = dot(d, d);
                if (dist > 1.0) discard;
                float alpha = exp(-2.0 * dist) * vColor.a;
                fragColor = vec4(vColor.rgb * alpha, alpha);
            }
        `);
        gl.compileShader(fs);

        this.program = gl.createProgram();
        gl.attachShader(this.program, vs);
        gl.attachShader(this.program, fs);
        gl.linkProgram(this.program);
        gl.useProgram(this.program);

        this.uViewProj = gl.getUniformLocation(this.program, 'viewProj');
        this.uFocal = gl.getUniformLocation(this.program, 'focal');
        this.uViewport = gl.getUniformLocation(this.program, 'viewport');
    }

    initView() {
        this.updateView();
    }

    updateView() {
        const cx = this.distance * Math.cos(this.phi) * Math.sin(this.theta);
        const cy = this.distance * Math.sin(this.phi);
        const cz = this.distance * Math.cos(this.phi) * Math.cos(this.theta);
        this.camPos = [cx, cy, cz];
        
        const f = [0, 0, 0];
        const up = [0, 1, 0];
        const z = [f[0]-cx, f[1]-cy, f[2]-cz];
        const zLen = Math.sqrt(z[0]*z[0]+z[1]*z[1]+z[2]*z[2]);
        z[0]/=zLen; z[1]/=zLen; z[2]/=zLen;
        const x = [up[1]*z[2]-up[2]*z[1], up[2]*z[0]-up[0]*z[2], up[0]*z[1]-up[1]*z[0]];
        const xLen = Math.sqrt(x[0]*x[0]+x[1]*x[1]+x[2]*x[2]);
        x[0]/=xLen; x[1]/=xLen; x[2]/=xLen;
        const y = [z[1]*x[2]-z[2]*x[1], z[2]*x[0]-z[0]*x[2], z[0]*x[1]-z[1]*x[0]];

        const view = new Float32Array([
            x[0], y[0], -z[0], 0,
            x[1], y[1], -z[1], 0,
            x[2], y[2], -z[2], 0,
            -(x[0]*cx+x[1]*cy+x[2]*cz), -(y[0]*cx+y[1]*cy+y[2]*cz), z[0]*cx+z[1]*cy+z[2]*cz, 1
        ]);

        const fov = 50, near = 0.1, far = 100;
        const t = 1/Math.tan(fov*Math.PI/360);
        const proj = new Float32Array([
            t, 0, 0, 0,
            0, t, 0, 0,
            0, 0, (far+near)/(near-far), -1,
            0, 0, 2*far*near/(near-far), 0
        ]);

        const vp = new Float32Array(16);
        for (let i = 0; i < 4; i++)
            for (let j = 0; j < 4; j++)
                vp[i*4+j] = view[j]*proj[i*4] + view[4+j]*proj[i*4+1] + view[8+j]*proj[i*4+2] + view[12+j]*proj[i*4+3];

        const gl = this.gl;
        gl.uniformMatrix4fv(this.uViewProj, false, vp);
        gl.uniform2f(this.uFocal, this.width/2, this.height/2);
        gl.uniform2f(this.uViewport, this.width, this.height);
    }

    load(url) {
        fetch(url)
            .then(r => r.arrayBuffer())
            .then(buf => {
                const count = Math.floor(buf.byteLength / 32);
                this.numGaussians = count;
                const gl = this.gl;
                
                if (this.vao) gl.deleteVertexArray(this.vao);
                this.vao = gl.createVertexArray();
                gl.bindVertexArray(this.vao);
                
                if (this.vbo) gl.deleteBuffer(this.vbo);
                this.vbo = gl.createBuffer();
                gl.bindBuffer(gl.ARRAY_BUFFER, this.vbo);
                gl.bufferData(gl.ARRAY_BUFFER, buf, gl.STATIC_DRAW);
                
                // Position (3 floats), Color (4 floats), Scale (3 floats), Rot (4 floats)
                const stride = 32;
                gl.vertexAttribPointer(0, 3, gl.FLOAT, false, stride, 0);
                gl.enableVertexAttribArray(0);
                gl.vertexAttribPointer(1, 4, gl.UNSIGNED_BYTE, true, stride, 12);
                gl.enableVertexAttribArray(1);
                gl.vertexAttribPointer(2, 3, gl.FLOAT, false, stride, 16);
                gl.enableVertexAttribArray(2);
                gl.vertexAttribPointer(3, 4, gl.FLOAT, false, stride, 28);
                gl.enableVertexAttribArray(3);
                
                gl.bindVertexArray(null);
                this.render();
            });
    }

    render() {
        if (!this.numGaussians) return;
        const gl = this.gl;
        gl.enable(gl.BLEND);
        gl.blendFunc(gl.ONE, gl.ONE_MINUS_SRC_ALPHA);
        gl.disable(gl.DEPTH_TEST);
        
        this.updateView();
        gl.bindVertexArray(this.vao);
        gl.drawArrays(gl.POINTS, 0, this.numGaussians);
        gl.bindVertexArray(null);
    }

    setupControls() {
        this.canvas.addEventListener('mousedown', e => {
            this.isDragging = true;
            this.lastX = e.clientX;
            this.lastY = e.clientY;
            this.autoRotate = false;
        });
        window.addEventListener('mousemove', e => {
            if (!this.isDragging) return;
            const dx = e.clientX - this.lastX;
            const dy = e.clientY - this.lastY;
            this.theta -= dx * 0.01;
            this.phi = Math.max(-1.5, Math.min(1.5, this.phi + dy * 0.01));
            this.lastX = e.clientX;
            this.lastY = e.clientY;
            this.render();
        });
        window.addEventListener('mouseup', () => { this.isDragging = false; });
        this.canvas.addEventListener('wheel', e => {
            this.distance = Math.max(1, Math.min(20, this.distance + e.deltaY * 0.01));
            this.render();
        });
    }

    resize() {
        const rect = this.canvas.getBoundingClientRect();
        this.width = rect.width * window.devicePixelRatio;
        this.height = rect.height * window.devicePixelRatio;
        this.canvas.width = this.width;
        this.canvas.height = this.height;
        this.gl.viewport(0, 0, this.width, this.height);
        if (this.numGaussians) this.render();
    }

    animate() {
        if (this.autoRotate) {
            this.theta += 0.005;
            this.render();
        }
        requestAnimationFrame(() => this.animate());
    }

    startAutoRotate() {
        this.autoRotate = true;
        this.animate();
    }
}

// Auto-init all splat containers
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.splat-viewer').forEach(el => {
        const viewer = new SplatViewer(el);
        viewer.load(el.dataset.splat);
        viewer.startAutoRotate();
        el.viewer = viewer;
    });
});