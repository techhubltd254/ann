'use strict';
(function(){const K='kicc.theme';
function apply(v){const t=v==='dark'?'dark':'light';document.documentElement.dataset.theme=t;document.documentElement.style.colorScheme=t;document.querySelectorAll('[data-as-theme]').forEach(b=>{b.textContent=t==='dark'?'☀':'◐';b.setAttribute('aria-pressed',String(t==='dark'))});}
let cur='light';try{cur=localStorage.getItem(K)||'dark'}catch(e){cur='dark'}
document.addEventListener('DOMContentLoaded',()=>{apply(cur);
 document.querySelectorAll('[data-as-theme]').forEach(b=>b.addEventListener('click',()=>{cur=cur==='dark'?'light':'dark';try{localStorage.setItem(K,cur)}catch(e){}apply(cur)}));
 const bg=document.querySelector('[data-as-burger]');if(bg)bg.addEventListener('click',()=>document.body.classList.toggle('nav-open'));
 document.querySelectorAll('.as-nav a').forEach(a=>a.addEventListener('click',()=>document.body.classList.remove('nav-open')));
 const s=document.querySelector('[data-as-search]');if(s)s.addEventListener('input',()=>{const q=s.value.toLowerCase();document.querySelectorAll('.as-card table tbody tr, .as-feed-item').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none'})});
 const n=document.querySelectorAll('.as-nav a');const cur_path=location.pathname;n.forEach(a=>{if(a.getAttribute('href')===cur_path)a.classList.add('active')});
});})();
