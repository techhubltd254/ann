'use strict';
document.addEventListener('DOMContentLoaded',()=>{
  const root=document.querySelector('[data-chunk-upload]');if(!root)return;
  const type=root.querySelector('[data-up-owner-type]'),id=root.querySelector('[data-up-owner-id]'),
        slot=root.querySelector('[data-up-slot]'),title=root.querySelector('[data-up-title]'),
        file=root.querySelector('[data-up-file]'),start=root.querySelector('[data-up-start]'),
        bar=root.querySelector('[data-up-bar]'),status=root.querySelector('[data-up-status]'),
        log=root.querySelector('[data-up-log]');
  const MAX=2*1024*1024*1024;
  const csrf=()=>document.querySelector('meta[name="csrf-token"]')?.content||'';
  const say=(t,err=false)=>{status.textContent=t;status.dataset.error=String(err);};
  const note=t=>{log.hidden=false;log.textContent+=t+'\n';log.scrollTop=log.scrollHeight;};
  function send(url,body,method='POST'){
    return new Promise((res,rej)=>{
      const x=new XMLHttpRequest();x.open(method,url);x.withCredentials=true;
      x.setRequestHeader('Accept','application/json');x.setRequestHeader('X-CSRF-TOKEN',csrf());
      x.onerror=()=>rej(Error('Network error — no successful upload was confirmed.'));
      x.onload=()=>{let d;try{d=JSON.parse(x.responseText);}catch{rej(Error('Server returned HTTP '+x.status+'.'));return;}
        (x.status>=200&&x.status<300)?res(d):rej(Error(d.message||('Rejected (HTTP '+x.status+')')));};
      x.send(body);
    });
  }
  start.addEventListener('click',async()=>{
    const f=file.files[0];
    if(!f)return say('Choose a video file first.',true);
    if(!id.value)return say('Owner ID is required.',true);
    if(f.size>MAX)return say('File is larger than the 2 GiB ceiling.',true);
    start.disabled=true;log.hidden=false;log.textContent='';
    note('file: '+f.name+' ('+(f.size/1048576).toFixed(1)+' MiB)');
    try{
      say('Opening an upload session…');
      const init=await send(root.dataset.base+'/init',(()=>{const d=new FormData();
        d.append('filename',f.name);d.append('size',String(f.size));d.append('mime',f.type||'video/mp4');
        d.append('owner_type',type.value);d.append('owner_id',id.value);d.append('slot',slot.value);
        d.append('title',title.value||f.name);return d;})());
      note('session '+init.upload_id+' · slice '+(init.chunk_bytes/1048576)+' MiB');
      const step=init.chunk_bytes;let index=0;
      for(let off=0;off<f.size;off+=step,index++){
        const d=new FormData();d.append('index',String(index));d.append('chunk',f.slice(off,Math.min(off+step,f.size)),f.name+'.part');
        const r=await send(root.dataset.base+'/'+init.upload_id+'/chunk',d);
        const pct=Math.min(100,Math.round(((off+step)/f.size)*100));
        bar.style.width=pct+'%';say('Uploading slice '+r.received+' · '+pct+'%');
      }
      say('Assembling and streaming to R2…');
      const done=await send(root.dataset.base+'/'+init.upload_id+'/complete',new FormData());
      bar.style.width='100%';
      note('stored id='+done.id+'\npath='+done.path+'\nbytes='+done.bytes+'\nstatus='+done.status+'\npublic='+done.public_url+'\nalgorithm='+(done.algorithm_state||'n/a'));
      say('Uploaded. Media #'+done.id+' is ready and the public caches were cleared.');
    }catch(e){say(e.message,true);note('FAILED: '+e.message);}
    finally{start.disabled=false;}
  });
});
