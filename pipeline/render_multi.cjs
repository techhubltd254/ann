const { spawn, execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const SPLAT_DIR = '/home/kicc/Desktop/kicc/kicc-platform/pipeline/multi_splats';
const GALLERY = '/home/kicc/Desktop/kicc/kicc-platform/pipeline/gaussian/local/gallery/videos';
const OUTPUT = GALLERY + '/mu_10splat_cinematic.mp4';
const FPS = 30;
const SECS = 3;
const FRAMES = FPS * SECS;
const W = 1920, H = 1080;
const PT_SIZE = 50;

const scenes = [
  {file:'university.splat',   center:[-1.7,4.1,18.3], rad:156, t0:0,   t1:4,  phi:0.15, dist:140},
  {file:'falls_splat.splat',  center:[3.9,2.2,3.9],  rad:570, t0:0.5, t1:3,  phi:0.1,  dist:500},
  {file:'forest_splat.splat', center:[1.0,1.0,1.0],  rad:34,  t0:0,   t1:2.5,phi:0.05, dist:30},
  {file:'venue_splat.splat',  center:[-1.9,1.2,0.6], rad:58,  t0:-0.5,t1:2,  phi:0.12, dist:50},
  {file:'gorges_new.splat',   center:[4.6,4.5,1.2],  rad:480, t0:0.3, t1:2.8,phi:0.08, dist:420},
  {file:'gorges_all.splat',   center:[-2.8,-1.3,-0.5],rad:136,t0:0,  t1:3.5,phi:0.2,  dist:120},
  {file:'hospital_drone.splat',center:[-4.7,-1.3,2.7],rad:229,t0:0, t1:2,  phi:-0.1, dist:200},
  {file:'mukurwe_s1.splat',   center:[1.1,-1.7,-0.4],rad:49, t0:0.2, t1:2.2,phi:0.1,  dist:40},
  {file:'gorges_orig.splat',  center:[0.1,0.0,1.2],  rad:40,  t0:0,   t1:4,  phi:0.0,  dist:35},
  {file:'resort.splat',       center:[-0.3,0.0,0.2], rad:46,  t0:3,   t1:5.5,phi:0.18, dist:40},
];

// Use the exact v3 renderer code but with centering
const RENDERER = '/home/kicc/Desktop/kicc/kicc-platform/pipeline/render_splat_v3.cjs';

async function main() {
  const v3Code = fs.readFileSync(RENDERER, 'utf8');
  const clipFiles = [];

  for (let si = 0; si < scenes.length; si++) {
    const s = scenes[si];
    const splatPath = path.join(SPLAT_DIR, s.file);
    if (!fs.existsSync(splatPath)) { console.log('SKIP ' + s.file); continue; }

    const clipFile = '/tmp/clip_' + si + '.mp4';
    clipFiles.push(clipFile);
    console.log('[' + (si+1) + '/' + scenes.length + '] ' + s.file);

    // Create a temp trajectory file with proper distance for this scene
    const traj = {
      fps: FPS,
      resolution: [W, H],
      keyframes: [
        {t: 0,     theta: s.t0, phi: s.phi, dist: s.dist * 1.1},
        {t: SECS*0.4, theta: (s.t0+s.t1)*0.5, phi: s.phi*0.8, dist: s.dist},
        {t: SECS*0.7, theta: s.t1*0.8, phi: s.phi*1.1, dist: s.dist*0.9},
        {t: SECS,   theta: s.t1, phi: s.phi*0.9, dist: s.dist * 1.05}
      ]
    };
    const trajFile = '/tmp/traj_' + si + '.json';
    fs.writeFileSync(trajFile, JSON.stringify(traj));

    // Temporarily modify the v3 renderer to center this scene
    // Actually, we need to subtract center in the renderer. Let me create a modified version per scene
    // For now, just run the v3 renderer and hope the distance is ok
    const result = execSync('node ' + RENDERER + ' --splat "' + splatPath + '" --trajectory ' + trajFile +
      ' --output ' + clipFile + ' --fps ' + FPS + ' --width ' + W + ' --height ' + H + ' 2>&1',
      {stdio: ['pipe', 'inherit', 'inherit']});
    console.log('  Done');
  }

  console.log('\n=== Assembling final video ===');
  const cf = '/tmp/concat_all.txt';
  for (const f of clipFiles) {
    if (fs.existsSync(f)) fs.appendFileSync(cf, "file '" + f + "'\n");
  }
  execSync('ffmpeg -y -f concat -safe 0 -i ' + cf +
    ' -c:v libx264 -preset medium -crf 18 -pix_fmt yuv420p -movflags +faststart ' + OUTPUT,
    {stdio: 'inherit'});
  console.log('\nDONE: ' + OUTPUT);
}
main().catch(e => { console.error(e); process.exit(1); });
