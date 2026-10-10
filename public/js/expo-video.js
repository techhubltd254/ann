/* Shared KICC player: Expo-style media, lower fade and centred caption.
 * Source URLs remain owned by the native media DTO/Blade resolver; no demo media. */
(function(){'use strict';
 const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const url=v=>{if(!v)return '';try{const u=new URL(v,location.origin);return ['https:','http:'].includes(u.protocol)?u.href:'';}catch{return '';}};
 function videoUrl(v){const raw=url(v);if(!raw)return '';const u=new URL(raw);if(u.origin===location.origin&&u.pathname.startsWith('/media/video/'))u.pathname=u.pathname.replace('/media/video/','/media/original/');else if(u.hostname==='kicc-r2-media.techhubltd254.workers.dev'&&u.pathname.startsWith('/storage/'))return location.origin+'/media/original/'+u.pathname.slice(9)+u.search;return u.href;}
 function render(o){
  const named={'#39':"'",apos:"'",amp:'&',quot:'\"',lt:'<',gt:'>',nbsp:' '};
  o={...o,title:String(o.title||'').replace(/&(#39|apos|amp|quot|lt|gt|nbsp);/g,(_,k)=>named[k])};
  const autoPlay=!navigator.connection?.saveData&&!matchMedia('(max-width:768px),(pointer:coarse),(prefers-reduced-motion:reduce)').matches;const selectedVideo=matchMedia('(max-width:768px)').matches&&o.mobileVideo?o.mobileVideo:o.video;const video=videoUrl(selectedVideo),poster=url(o.poster),hero=o.variant==='hero';
  const actions=o.actions||[];
  return `<section class="expo-video ${hero?'expo-video--hero':'expo-video--feature'}" ${o.id?`id="${esc(o.id)}"`:''} ${o.fallbackImage?`data-ai-fallback="${esc(url(o.fallbackImage))}"`:""} data-expo-video data-expo-owner="${esc(o.ownerId||'')}" data-expo-state="${video?'video':'poster'}">
   <div class="expo-video__asset">${poster?`<img class="expo-video__poster" src="${esc(poster)}" alt="${esc(o.title)}" ${hero?'fetchpriority="high"':'loading="lazy"'}>`:''}${video?`<video class="expo-video__film" muted ${autoPlay?'autoplay':''} loop playsinline preload="${autoPlay?'metadata':'none'}" ${poster?`poster="${esc(poster)}"`:''} aria-label="${esc(o.title)}"><source src="${esc(video)}"></video>`:''}</div>
   <div class="expo-video__content">${o.eyebrow?`<p class="expo-video__eyebrow">${esc(o.eyebrow)}</p>`:''}<${hero?'h1':'h2'} class="expo-video__title">${esc(o.title)}</${hero?'h1':'h2'}>${o.description?`<p class="expo-video__text">${esc(o.description)}</p>`:''}<div class="expo-video__actions">${actions.map((a,i)=>`<a class="${i?'expo-video__secondary':'expo-video__button'}" href="${esc(a.href)}" ${a.scroll?`data-scroll="${esc(a.scroll)}"`:''}>${esc(a.label)}${i?'':' <span aria-hidden="true">↗</span>'}</a>`).join('')}</div></div>
   ${!video?'<span class="expo-video__availability">Image preview · no video published in this slot</span>':''}
  </section>`;
 }
 function fallback(host,v){
  if(!host?.dataset.aiFallback)return false;
  v.hidden=true;host.dataset.expoPlayback='ai-fallback';
  let img=host.querySelector('.expo-video__poster,.expo-video__fallback');
  if(!img){img=document.createElement('img');img.className='expo-video__poster';img.alt='Media awaiting upload';(host.querySelector('.expo-video__asset')||host).prepend(img);}
  img.src=host.dataset.aiFallback;img.hidden=false;
  let label=host.querySelector('[data-ai-fallback-label]');if(!label){label=document.createElement('span');label.dataset.aiFallbackLabel='1';label.className='expo-video__availability';label.textContent='Video could not start · tap Play to retry';host.append(label);}
  return true;
 }
 const watched=new WeakSet();let activeTile=null,queued=false;
 function feature(host,v){
  if(host.matches('.public-media')){
   host.classList.add('expo-video','expo-video--feature');v.controls=false;
   const asset=document.createElement('div');asset.className='expo-video__asset';host.prepend(asset);asset.append(v);
   const content=document.createElement('div');content.className='expo-video__content';
   [...host.children].filter(e=>e!==asset).forEach(e=>{if(e.matches('h1,h2,h3'))e.classList.add('expo-video__title');if(e.matches('p'))e.classList.add('expo-video__text');content.append(e);});host.append(content);
   if(v.poster){const img=document.createElement('img');img.src=v.poster;img.alt=content.querySelector('h1,h2,h3')?.textContent||'';img.className='expo-video__poster';asset.prepend(img);}
  }
 }
 const visible=new IntersectionObserver(entries=>entries.forEach(e=>{
  const v=e.target;if(matchMedia('(max-width:768px),(pointer:coarse)').matches){if(document.hidden||!e.isIntersecting)v.pause();return;}if(document.hidden||!e.isIntersecting){v.pause();return;}
  const tile=v.closest('[data-expo-variant="tile"]');
  if(tile&&matchMedia('(pointer:fine)').matches)return;
  if(tile){if(e.intersectionRatio<.6){v.pause();return;}if(activeTile&&activeTile!==v)activeTile.pause();activeTile=v;}
  if(!navigator.connection?.saveData&&!matchMedia('(max-width:768px),(pointer:coarse),(prefers-reduced-motion:reduce)').matches)v.play().catch(()=>{const host=v.closest('[data-expo-video]');if(!fallback(host,v))host?.setAttribute('data-expo-playback','tap-required');});
 }),{threshold:[0,.25,.6,1]});
 function attach(host,v,tile){
  if(v.dataset.hierarchyManaged||watched.has(v))return;
  let changed=false;const current=v.getAttribute('src');if(current&&videoUrl(current)!==url(current)){v.src=videoUrl(current);changed=true;}v.querySelectorAll('source[src]').forEach(el=>{const old=el.getAttribute('src'),next=videoUrl(old);if(next!==url(old)){el.src=next;changed=true;}});if(changed)v.load();
  watched.add(v);if(!tile)feature(host,v);host.dataset.expoVideo='';if(tile)host.dataset.expoVariant='tile';
  host.classList.add('expo-video-upgraded');v.classList.add('expo-video__film');v.muted=true;v.playsInline=true;v.loop=!v.closest('[data-live-stream]');if(matchMedia('(max-width:768px),(pointer:coarse)').matches){v.autoplay=false;v.preload='none';}
  if(tile){v.autoplay=false;v.pause();host.addEventListener('pointerenter',e=>{if(e.pointerType==='mouse'&&!matchMedia('(max-width:768px),(pointer:coarse)').matches)v.play().catch(()=>{});});host.addEventListener('pointerleave',()=>v.pause());host.addEventListener('focusin',()=>{if(!matchMedia('(max-width:768px),(pointer:coarse)').matches)v.play().catch(()=>{});});host.addEventListener('focusout',()=>v.pause());}else v.autoplay=!navigator.connection?.saveData&&!matchMedia('(max-width:768px),(pointer:coarse),(prefers-reduced-motion:reduce)').matches;
  // Transport is real, not a fake play toast. Leave native HLS quality/seek controls intact.
  const controls=document.createElement('div');controls.className='expo-video__controls';
  const pause=document.createElement('button');pause.type='button';pause.textContent='Pause';pause.setAttribute('aria-label','Pause video');
  const sound=document.createElement('button');sound.type='button';sound.textContent='Sound on';sound.setAttribute('aria-label','Enable video sound');
  pause.onclick=e=>{e.stopPropagation();if(v.hidden||v.paused){v.hidden=false;v.play().then(()=>{host.querySelector('[data-ai-fallback-label]')?.remove();}).catch(()=>{if(!fallback(host,v))v.controls=true;});}else v.pause();};
  sound.onclick=e=>{e.stopPropagation();v.muted=!v.muted;sound.textContent=v.muted?'Sound on':'Mute';sound.setAttribute('aria-label',v.muted?'Enable video sound':'Mute video');};
  const sync=()=>{pause.textContent=v.paused?'Play':'Pause';pause.setAttribute('aria-label',v.paused?'Play video':'Pause video');host.dataset.expoPlayback=v.error?'unavailable':v.paused?'paused':'playing';};
  v.addEventListener('play',sync);v.addEventListener('pause',sync);
  v.addEventListener('error',()=>{if(fallback(host,v)){pause.textContent='Play';pause.disabled=false;sound.hidden=true;return;}host.dataset.expoPlayback='unavailable';v.hidden=true;pause.textContent='Video unavailable';pause.disabled=true;sound.hidden=true;if(v.poster&&!host.querySelector('img')){const img=document.createElement('img');img.src=v.poster;img.alt='Published video poster';img.className='expo-video__fallback';(host.querySelector('.expo-video__asset')||host).prepend(img);}});
  controls.append(pause,sound);host.append(controls);visible.observe(v);sync();
 }
 function scan(){queued=false;if(document.body.classList.contains('admin-shell')||/\/(?:portal|.*admin)(\/|$)/.test(location.pathname))return;
  document.querySelectorAll('#app video,main video,.sx-hero video').forEach(v=>{
   if(v.closest('[data-upload-preview],dialog,[data-selected-preview],.modal,.splat-gate,.viewer3d,.official-kicc-films'))return;
   let host=v.closest('[data-expo-video],.hls-stage,.hologram,.cinematic-media,.media-tile,.tm-window,.tile-media,.orbit-screen,.pc-media,.ex-shot,.rb-media');
   if(!host){host=v.closest('.public-media');if(!host){host=document.createElement('div');host.className='expo-video-native';v.parentElement.insertBefore(host,v);host.append(v);}if(host.tagName==='PICTURE')return;}
   const tile=!!host.closest('.tm-tile,.prod-card,.orbit-screen,.tile,.media-tile,.ex-card')&&!host.classList.contains('hls-stage')&&!v.closest('.media-tile[data-county-hero="1"]');
   attach(host,v,tile);
  });
 }
 function schedule(){if(queued)return;queued=true;requestAnimationFrame(scan);}
 window.KiccExpoVideo={render,scan:schedule};
 document.addEventListener('DOMContentLoaded',()=>{scan();new MutationObserver(schedule).observe(document.getElementById('app')||document.body,{childList:true,subtree:true});});
 document.addEventListener('visibilitychange',()=>{if(document.hidden)document.querySelectorAll('.expo-video__film').forEach(v=>v.pause());else schedule();});
})();
