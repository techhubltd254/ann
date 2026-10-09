'use strict';
document.addEventListener('DOMContentLoaded',()=>{
 const root=document.querySelector('[data-chunk-upload]');if(!root)return;
 const get=n=>root.querySelector('[data-up-'+n+']');
 const county=get('county'),type=get('owner-type'),owner=get('owner-id'),sector=get('sector'),slot=get('slot'),title=get('title'),file=get('file'),start=get('start'),progress=get('progress'),status=get('status'),log=get('log');
 const entities=JSON.parse(get('entities').textContent);let busy=false;
 const say=t=>status.textContent=t;
 const choices=(sel,list,label)=>{sel.replaceChildren(new Option(label,''),...list.map(x=>new Option(x.name,String(x.id))));};
 const refresh=()=>{sector.disabled=true;choices(sector,[],'Choose institution first');const kind=type.value.split('\\').pop();const rows=kind==='County'?entities.counties.filter(x=>String(x.id)===county.value):kind==='CountyInstitution'?entities.institutions.filter(x=>String(x.county_id)===county.value):entities.venues;choices(owner,rows,'Choose responsible owner');};
 county.addEventListener('change',refresh);type.addEventListener('change',refresh);
 owner.addEventListener('change',async()=>{sector.disabled=true;if(type.value!=='App\\Models\\CountyInstitution'||!owner.value)return;try{const r=await fetch('/portal/media-flow/'+owner.value+'/sectors',{headers:{Accept:'application/json'}});const d=await r.json();if(!r.ok)throw Error(d.message||'Cannot load sectors');choices(sector,d.sectors,'Choose linked sector');sector.disabled=false;}catch(e){say(e.message);}});
 async function send(path,body){
  for(let attempt=0;attempt<20;attempt++){
   const r=await fetch(root.dataset.base+path,{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body});
   if(r.status===429||r.status===502||r.status===503||r.status===504||r.status===524||(r.status===409&&path.endsWith("/complete"))){await new Promise(ok=>setTimeout(ok,Math.min(60000,Number(r.headers.get('Retry-After')||5)*1000)));continue;}
   const d=await r.json().catch(()=>({message:'HTTP '+r.status}));if(!r.ok)throw Error(d.message||'HTTP '+r.status);return d;
  }throw Error('Server busy. The upload was not confirmed; no successful publication is being claimed.');
 }
 start.addEventListener('click',async()=>{
  if(busy)return;const f=file.files[0];
  if(!f||!owner.value||!title.value.trim())return say('Select a file, owner and title.');
  if(type.value==='App\\Models\\CountyInstitution'&&!sector.value)return say('Select a linked sector.');
  if(f.size>2147483648)return say('Maximum file size is 2 GiB.');
  busy=true;start.disabled=true;log.hidden=false;log.textContent='';
  try{
   const form=new FormData();Object.entries({filename:f.name,size:f.size,mime:f.type||'video/mp4',owner_type:type.value,owner_id:owner.value,slot:slot.value,title:title.value}).forEach(([k,v])=>form.append(k,v));if(sector.value)form.append('sector_id',sector.value);
   say('Starting verified upload…');const init=await send('/init',form);
   for(let offset=0,index=0;offset<f.size;offset+=init.chunk_bytes,index++){
    const part=new FormData();part.append('index',index);part.append('chunk',f.slice(offset,Math.min(offset+init.chunk_bytes,f.size)),'chunk.bin');
    await send('/'+init.upload_id+'/chunk',part);progress.value=Math.min(100,Math.round((offset+init.chunk_bytes)/f.size*100));say('Upload '+progress.value+'% · verifying slice '+(index+1));
   }
   say('Verifying complete file in R2 and publishing…');const result=await send('/'+init.upload_id+'/complete',new FormData());
   log.textContent=JSON.stringify(result,null,2);say('Media #'+result.id+' saved. '+result.bytes+' bytes verified in R2. Refresh the public owner page to see the change.');
  }catch(e){say('Not published: '+e.message);}finally{busy=false;start.disabled=false;}
 });
 const q=new URLSearchParams(location.search);if(q.has('county'))county.value=q.get('county');refresh();
});
