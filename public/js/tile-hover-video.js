(function tileHoverVideo(){
 const esc=x=>String(x??'');
 const mediaOf=href=>{const t=(window.KICC_NATIVE?.tables?.media)||[];return t.find(m=>m.kind==='video'&&m.target===href);};
 function attach(el){
  const a=el.querySelector('a[href^="#/counties/"],a[href^="#/institutions/"],a[href^="#/venues/"],a[href^="#/marketplace/"]');if(!a)return;
  const m=mediaOf(a.getAttribute('href').slice(1));if(!m||!m.url)return;
  if(el.querySelector('video[data-hover-video]'))return;
  const host=el.querySelector('.tile-media,.pc-media,.tm-window,.orbit-screen')||el;
  host.style.position=host.style.position||'relative';
  const v=document.createElement('video');v.dataset.hoverVideo='1';v.muted=true;v.loop=true;v.playsInline=true;v.preload='none';if(m.poster)v.poster=m.poster;v.src=m.url;
  v.style.cssText='position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity .35s ease;pointer-events:none';
  host.appendChild(v);
  const play=()=>{v.preload='auto';v.style.opacity='1';v.play().catch(()=>{});};
  const stop=()=>{v.pause();v.style.opacity='0';};
  el.addEventListener('pointerenter',e=>{if(e.pointerType!=='touch')play()});
  el.addEventListener('pointerleave',stop);el.addEventListener('focusin',play);el.addEventListener('focusout',stop);
  if(matchMedia('(pointer: coarse)').matches){ // Instagram-style: the tile in view plays, others pause
   const io=new IntersectionObserver(es=>es.forEach(en=>{if(en.intersectionRatio>=.6)play();else stop();}),{threshold:[0,.6,1]});io.observe(el);
  }
 }
 const scan=()=>document.querySelectorAll('.tile,.prod-card,.tm-tile,.orbit-screen').forEach(attach);
 new MutationObserver(scan).observe(document.getElementById('app')||document.body,{childList:true,subtree:true});
 document.addEventListener('DOMContentLoaded',scan);setTimeout(scan,800);
})();

