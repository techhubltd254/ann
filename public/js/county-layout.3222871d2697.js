/* Public county media depth. No generated imagery or placeholder assets. */
(()=>{'use strict';
 const reduce=matchMedia('(prefers-reduced-motion: reduce)');
 let queued=false;
 function update(){
  queued=false;
  document.querySelectorAll('[data-orbit-stage]').forEach(stage=>{
   if(matchMedia('(prefers-reduced-motion: reduce)').matches){stage.dataset.countyReduced='true';stage.style.removeProperty('--county-scroll-tilt');stage.style.setProperty('transform','none','important');stage.style.setProperty('transition','none','important');return;}
   if(stage.dataset.countyReduced){delete stage.dataset.countyReduced;stage.style.removeProperty('transform');stage.style.removeProperty('transition');}
   const rect=stage.getBoundingClientRect();
   if(rect.bottom<0||rect.top>innerHeight)return;
   const position=Math.max(-1,Math.min(1,((rect.top+rect.height/2)-innerHeight/2)/innerHeight));
   stage.style.setProperty('--county-scroll-tilt',(position*(innerWidth<700?1.1:2.4)).toFixed(2)+'deg');
  });
 }
 function schedule(){if(!queued){queued=true;requestAnimationFrame(update);}}
 function scan(){
  const app=document.getElementById('app');if(!app)return;
  app.querySelectorAll('#chapter-nation img,#countyFull img,.hierarchy-tile[data-hierarchy-media-state="empty"] img').forEach(img=>{
   if(/\/placeholders\/|media-pending|existing-generated-preview/i.test(img.getAttribute('src')||''))img.remove();
  });
  schedule();
 }
 addEventListener('scroll',schedule,{passive:true});
 addEventListener('resize',schedule,{passive:true});
 reduce.addEventListener('change',update);
 document.addEventListener('DOMContentLoaded',()=>{scan();const app=document.getElementById('app');if(app)new MutationObserver(scan).observe(app,{childList:true,subtree:true});});
 window.KiccCountyLayout={release:'responsive-v1',scan};
})();
