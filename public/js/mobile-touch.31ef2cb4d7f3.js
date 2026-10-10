/* Native browser rails: never cancel finger gestures or page scrolling. */
(()=>{'use strict';
 const media=matchMedia('(max-width:768px),(pointer:coarse)');
 function scan(){
  if(!media.matches)return;
  document.querySelectorAll('[data-cat],[data-cregion]').forEach(button=>{const row=button.parentElement;if(!row.classList.contains('mobile-filter-rail')){row.classList.add('mobile-filter-rail');row.setAttribute('aria-label',button.hasAttribute('data-cat')?'Product categories. Swipe for more filters.':'County regions. Swipe for more filters.');}});
  const rail=document.getElementById('countyFull');
  if(rail&&!rail.dataset.touchScrollBound){
   rail.dataset.touchScrollBound='true';rail.setAttribute('aria-label','County cards. Swipe horizontally in Strip view.');
   const hint=document.createElement('p');hint.className='wrap muted-xs';hint.dataset.countyTouchHint='true';hint.textContent='Swipe left or right to explore counties. Use Grid for a compact overview.';rail.before(hint);
   rail.addEventListener('scroll',()=>{rail.dataset.userScrolled='true';},{passive:true});
   rail.addEventListener('keydown',e=>{if(e.target!==rail)return;if(e.key==='ArrowLeft'||e.key==='ArrowRight'){e.preventDefault();rail.scrollBy({left:(e.key==='ArrowRight'?1:-1)*rail.clientWidth*.9,behavior:matchMedia('(prefers-reduced-motion:reduce)').matches?'instant':'smooth'});}});
   rail.tabIndex=0;
  }
  document.querySelectorAll('.county-layout-card[hidden]').forEach(card=>{card.style.display='none';});
 }
 let queued=false;function schedule(){if(!queued){queued=true;requestAnimationFrame(()=>{queued=false;scan();});}}
 document.addEventListener('DOMContentLoaded',()=>{scan();new MutationObserver(schedule).observe(document.getElementById('app')||document.body,{childList:true,subtree:true});});
 addEventListener('hashchange',()=>{document.documentElement.classList.remove('mobile-nav-open');document.getElementById('mobileMenu')?.classList.remove('open');schedule();});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'){document.documentElement.classList.remove('mobile-nav-open');document.getElementById('mobileMenu')?.classList.remove('open');}});
 media.addEventListener('change',schedule);
 window.KiccMobileTouch={release:'native-swipe-v1',scan};
})();
