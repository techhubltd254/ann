'use strict';
document.addEventListener('DOMContentLoaded',()=>{
 const root=document.querySelector('[data-media-flow]');if(!root)return;
 const county=root.querySelector('[data-flow-county]'),inst=root.querySelector('[data-flow-institution]'),sector=root.querySelector('[data-flow-sector]'),media=root.querySelector('[data-flow-media]'),status=root.querySelector('[data-flow-status]'),display=root.querySelector('[data-flow-display]'),upload=root.querySelector('[data-flow-upload]'),actions=root.querySelector('[data-flow-actions]'),preview=root.querySelector('[data-selected-preview]'),progress=root.querySelector('[data-flow-progress]');
 const choices=[...inst.options].filter(o=>o.value).map(o=>({id:o.value,name:o.textContent,county:o.dataset.county,slug:o.dataset.slug}));
 let rows=[],busy=false,epoch=0;
 const opts=(sel,rows,label)=>{sel.replaceChildren(new Option(label,''),...rows.map(r=>new Option(r.name,String(r.id))));sel.disabled=!rows.length;};
 const message=(text,error=false)=>{status.textContent=text;status.dataset.error=String(error);};
 const clear=()=>{rows=[];opts(media,[],'Choose sector first');upload.hidden=true;actions.hidden=true;display.replaceChildren();preview.pause();preview.removeAttribute('src');};
 async function read(url){const response=await fetch(url,{credentials:'same-origin',headers:{Accept:'application/json'}});const body=await response.json();if(!response.ok)throw Error(body.message||'Cannot read the selected owner.');return body;}
 async function loadSectors(){const current=++epoch;clear();opts(sector,[],'Loading linked sectors…');if(!inst.value)return;
  try{const data=await read(root.dataset.base+'/'+encodeURIComponent(choices.find(c=>c.id===inst.value).slug)+'/sectors');if(current!==epoch)return;opts(sector,data.sectors,'Choose linked sector');message(data.sectors.length?'Choose a sector linked by the original system.':'This institution has no sector_entities link. Assign it in the hierarchy admin before uploading.');}catch(e){message(e.message,true);opts(sector,[],'Unavailable');}}
 async function loadMedia(selected=''){const current=++epoch;clear();if(!inst.value||!sector.value)return;message('Loading owned media and the current algorithm pick…');
  try{const data=await read(root.dataset.base+'/'+encodeURIComponent(choices.find(c=>c.id===inst.value).slug)+'/media?sector_id='+sector.value);if(current!==epoch)return;rows=data.media;opts(media,rows.map(a=>({id:a.id,name:'#'+a.id+' · '+a.kind+' · '+a.slot+' · '+a.path.split('/').pop()})),'Choose media ID');
   const h=document.createElement('h2');h.textContent='Current public hero: '+data.display.state;const p=document.createElement('p');p.textContent=data.display.reason;display.append(h,p);
   if(data.display.video){const video=document.createElement('video');video.controls=true;video.playsInline=true;video.preload='none';video.src=data.display.video;display.append(video);}
   upload.hidden=false;message(rows.length+' owned media records. Select a video to replace/delete, or add a new video.');if(selected&&rows.some(a=>String(a.id)===String(selected))){media.value=String(selected);showSelection();}
  }catch(e){message(e.message,true);}}
 function showSelection(){preview.pause();preview.removeAttribute('src');const row=rows.find(a=>String(a.id)===media.value);actions.hidden=!row||row.kind!=='video';if(actions.hidden)return;
  root.querySelector('[data-selected-details]').textContent='#'+row.id+' · '+row.path+' · '+row.algorithm_state+(row.legacy_shared_scope?' · Legacy institution-wide assignment; not sector-specific.':'');preview.hidden=!row.serves;if(row.serves)preview.src=row.serves;}
 function write(url,method,form){return new Promise((resolve,reject)=>{const xhr=new XMLHttpRequest();if(method==='DELETE'){form.append('_method','DELETE');method='POST';}xhr.open(method,url);xhr.withCredentials=true;xhr.setRequestHeader('Accept','application/json');xhr.setRequestHeader('X-CSRF-TOKEN',document.querySelector('meta[name="csrf-token"]').content);
  xhr.upload.onprogress=e=>{if(e.lengthComputable){progress.hidden=false;progress.value=Math.round(e.loaded/e.total*100);}};
  xhr.onerror=()=>reject(Error('Network error. No successful publication was confirmed.'));
  xhr.onload=()=>{let data;try{data=JSON.parse(xhr.responseText);}catch{reject(Error('Upload failed at the gateway or server (HTTP '+xhr.status+').'));return;}if(xhr.status>=200&&xhr.status<300)resolve(data);else reject(Error(data.message||'Request rejected (HTTP '+xhr.status+').'));};xhr.send(form);
 });}
 async function mutate(url,method,form,selected=''){if(busy)return;busy=true;root.querySelectorAll('button').forEach(b=>b.disabled=true);message('Saving to R2 and the native database…');
  try{const data=await write(url,method,form);await loadMedia(data.id||selected);message(data.deleted_id?'Media #'+data.deleted_id+' deleted. Shared R2 references were preserved.':'Media #'+data.id+' saved and verified by the server. HLS processing runs separately.');}
  catch(e){message(e.message,true);}finally{busy=false;root.querySelectorAll('button').forEach(b=>b.disabled=false);progress.hidden=true;}}
 async function transfer(form,replaceId=null){if(busy)return;const f=form.querySelector('[name="video"]').files[0];if(!f)return message('Choose a video.',true);busy=true;const control={controller:new AbortController()};control.signal=control.controller.signal;progress.hidden=false;try{const current=rows.find(a=>String(a.id)===String(replaceId));const result=await KiccEntityUploader.upload(f,{owner_type:'App\\Models\\CountyInstitution',owner_id:inst.value,sector_id:sector.value,slot:current?.slot||form.querySelector('[name="slot"]').value,title:current?.title||form.querySelector('[name="title"]')?.value||f.name,replace_id:replaceId},(n,t)=>{progress.value=n;message(t);},control);await loadMedia(result.id);message('Storage verified for media #'+result.id+'. Public playback updates automatically when ready.');}catch(e){message(e.message,true);}finally{busy=false;progress.hidden=true;}}

 county.addEventListener('change',()=>{if(busy)return;++epoch;clear();opts(sector,[],'Choose institution first');opts(inst,choices.filter(c=>c.county===county.value),'Choose institution');message('Choose the responsible institution.');});
 inst.addEventListener('change',()=>{if(!busy)loadSectors();});sector.addEventListener('change',()=>{if(!busy)loadMedia();});media.addEventListener('change',showSelection);
 upload.addEventListener('submit',e=>{e.preventDefault();transfer(upload);});
 root.querySelector('[data-flow-replace]').addEventListener('submit',e=>{e.preventDefault();transfer(e.target,media.value);});
 root.querySelector('[data-flow-delete]').addEventListener('click',()=>{if(!media.value||!confirm('Delete selected media #'+media.value+' from this owner? Shared objects will be retained.'))return;const data=new FormData();data.append('sector_id',sector.value);mutate(root.dataset.base+'/'+encodeURIComponent(choices.find(c=>c.id===inst.value).slug)+'/videos/'+media.value,'DELETE',data);});
 const initial=new URLSearchParams(location.search).get('institution');const match=choices.find(c=>c.id===initial);if(match){county.value=match.county;county.dispatchEvent(new Event('change'));inst.value=match.id;loadSectors();}
});
