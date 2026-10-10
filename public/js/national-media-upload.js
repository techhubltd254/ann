(()=>{'use strict';
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
document.addEventListener('DOMContentLoaded',()=>{
 const csrf=document.querySelector('meta[name="csrf-token"]')?.content;if(!csrf)return;
 document.querySelector('[data-nm-search]')?.addEventListener('input',e=>document.querySelectorAll('[data-nm-ministry]').forEach(x=>x.hidden=!x.dataset.nmMinistry.includes(e.target.value.toLowerCase())));
 for(const root of document.querySelectorAll('[data-national-upload]')){
  const el=n=>root.querySelector('[data-nm-'+n+']');const file=el('file'),title=el('title'),start=el('start'),pause=el('pause'),cancel=el('cancel'),progress=el('progress'),status=el('status');
  const key=['kicc.national.upload',root.dataset.actorId,root.dataset.ownerType,root.dataset.ownerId,root.dataset.slot].join(':');let busy=false,paused=false,request=null;start.disabled=false;
  const stored=()=>{try{return JSON.parse(localStorage.getItem(key)||'null')}catch{return null}};const remember=s=>localStorage.setItem(key,JSON.stringify(s));
  if(stored()){status.textContent='Pending upload found. Reselect the same file to resume from server-confirmed chunks.';cancel.hidden=false;}
  async function api(path,method='POST',body=null){for(let attempt=0;attempt<12;attempt++){
   if(paused)throw Error('Paused. Reselect the same file and choose Resume upload.');request=new AbortController();let r;
   try{r=await fetch(root.dataset.base+path,{method,body,credentials:'same-origin',signal:request.signal,headers:{Accept:'application/json','X-CSRF-TOKEN':csrf}});}catch(e){if(paused)throw Error('Paused. Resume from confirmed chunks when ready.');if(attempt===11)throw Error('Connection interrupted. Reselect the same file to resume.');status.textContent='Connection interrupted; retrying the same chunk…';await sleep(3000);continue;}
   if([429,502,503,504,524].includes(r.status)||(r.status===409&&path.endsWith('/complete'))){status.textContent='Server busy; retrying without restarting the upload…';await sleep(Math.min(30000,Number(r.headers.get('Retry-After')||5)*1000));continue;}
   const data=await r.json().catch(()=>({message:'Unexpected server response (HTTP '+r.status+').'}));
   if(r.status===401||r.status===419)throw Error('Your sign-in expired. Sign in again, return here and reselect the same file to resume.');
   if(!r.ok){const error=Error(Object.values(data.errors||{}).flat().join(' ')||data.message||'HTTP '+r.status);error.http=r.status;throw error;}return data;
  }throw Error('Could not confirm the request. Your progress is saved; reselect the same file to resume.');}
  root.querySelector('form[data-nm-form]').addEventListener('submit',e=>e.preventDefault());
  pause.addEventListener('click',()=>{paused=true;request?.abort();});
  cancel.addEventListener('click',async()=>{if(busy)return;const state=stored();try{if(state)await api('/'+state.upload_id,'DELETE');localStorage.removeItem(key);cancel.hidden=true;progress.value=0;status.textContent='Pending upload cancelled; live videos were not changed.';}catch(e){status.textContent=e.message;}});
  start.addEventListener('click',async()=>{
   if(busy)return;const f=file.files[0];if(!f){status.textContent='Choose a video file first.';return;}
   if(f.size>2147483648){status.textContent='Maximum file size is 2 GiB (2,147,483,648 bytes).';return;}
   if(!/\.(mp4|m4v|mov|webm|mkv)$/i.test(f.name)){status.textContent='Choose MP4, MOV, WebM or MKV video.';return;}
   busy=true;paused=false;start.disabled=true;pause.hidden=false;cancel.hidden=true;
   try{
    const head=new Uint8Array(await f.slice(0,65536).arrayBuffer());const hash=Array.from(new Uint8Array(await crypto.subtle.digest('SHA-256',head))).map(x=>x.toString(16).padStart(2,'0')).join('');
    const signature=[f.name,f.size,f.lastModified,hash].join('|');let state=stored();
    if(state&&state.signature!==signature)throw Error('Another file has pending progress. Cancel it first, or reselect the original file.');
    if(!state){const body=new FormData();const mime=f.type||(/\.mov$/i.test(f.name)?'video/quicktime':/\.webm$/i.test(f.name)?'video/webm':/\.mkv$/i.test(f.name)?'video/x-matroska':'video/mp4');Object.entries({filename:f.name,size:f.size,mime,owner_type:root.dataset.ownerType,owner_id:root.dataset.ownerId,slot:root.dataset.slot,title:title.value||f.name}).forEach(([k,v])=>body.append(k,v));const result=await api('/init','POST',body);state={signature,upload_id:result.upload_id,chunk_bytes:result.chunk_bytes};remember(state);}
    let confirmed;try{confirmed=await api('/'+state.upload_id+'/status','GET');}catch(e){if([404,410].includes(e.http)){localStorage.removeItem(key);throw Error('This pending upload expired. Start a new upload; the live video is unchanged.');}throw e;}
    if(!confirmed.result){const total=Math.ceil(f.size/state.chunk_bytes);
     for(let i=confirmed.next;i<total;i++){if(paused)throw Error('Paused; progress is saved. Resume with the same file.');const body=new FormData();body.append('index',i);body.append('chunk',f.slice(i*state.chunk_bytes,Math.min(f.size,(i+1)*state.chunk_bytes)),'part.bin');await api('/'+state.upload_id+'/chunk','POST',body);progress.value=Math.min(95,(i+1)*state.chunk_bytes/f.size*95);status.textContent='Uploaded '+Math.min(f.size,(i+1)*state.chunk_bytes).toLocaleString()+' / '+f.size.toLocaleString()+' bytes · verified chunk '+(i+1)+' of '+total;}
     status.textContent='All chunks received. Verifying the file and staging a draft…';confirmed.result=await api('/'+state.upload_id+'/complete','POST',new FormData());
    }
    if(confirmed.result.bytes!==f.size)throw Error('Final size verification failed. Nothing is confirmed published.');progress.value=100;localStorage.removeItem(key);status.textContent='Upload verified. Draft asset '+confirmed.result.id+' is processing; the current live video is unchanged. Preview and publish when ready.';root.dataset.completedAsset=confirmed.result.id;setTimeout(()=>location.reload(),2000);
   }catch(e){status.textContent=e.message;start.textContent='Resume upload';cancel.hidden=!stored();}finally{busy=false;start.disabled=false;pause.hidden=true;request=null;}
  });
 }
 // Only refresh while processing, never interrupt a file selection or active upload.
 if(document.querySelector('[data-nm-processing]'))setTimeout(()=>{if(!document.querySelector('[data-nm-file]:valid')&&!document.querySelector('[data-nm-start]:disabled'))location.reload();},20000);
});})();
