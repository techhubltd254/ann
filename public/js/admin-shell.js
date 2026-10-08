'use strict';
(function(){
 const KEY='kicc.theme';
 function apply(v){const t=v==='dark'?'dark':'light';document.documentElement.dataset.theme=t;document.documentElement.style.colorScheme=t;document.querySelectorAll('[data-as-theme]').forEach(b=>{b.textContent=t==='dark'?'Light':'Dark';b.setAttribute('aria-pressed',String(t==='dark'))});}
 let cur='light';try{cur=localStorage.getItem(KEY)||(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light')}catch(e){}
 document.addEventListener('DOMContentLoaded',()=>{apply(cur);
  document.querySelectorAll('[data-as-theme]').forEach(b=>b.addEventListener('click',()=>{cur=cur==='dark'?'light':'dark';try{localStorage.setItem(KEY,cur)}catch(e){}apply(cur)}));
  // Keep each portal's tab in the URL so refresh returns to the same tab (works with the portals' own Alpine state via ?tab=)
  document.querySelectorAll('[data-as-tab]').forEach(a=>a.addEventListener('click',()=>{try{history.replaceState(null,'',a.getAttribute('href'))}catch(e){}}));
 });
})();
