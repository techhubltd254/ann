@extends('layouts.app')
@section('title','Media flow — KICC')
@section('content')
<x-experience.head eyebrow="Institution → Sector → Media" title="Sequential media control." lead="Pick the responsible institution, then its sector, then the media. Upload, replace and delete only appear once you are inside the owner that the media belongs to." />
<section class="wrap admin-module">
  <div class="rf-flow" data-media-flow
       data-sectors-url="{{ url('/portal/media-flow') }}"
       data-upload-url="{{ url('/institution-admin') }}">
    <div><label>1 · Institution<select data-flow-institution><option value="">Choose institution</option>@foreach($institutions as $i)<option value="{{ $i->id }}" data-slug="{{ $i->slug }}">{{ $i->name }} · {{ $i->county?->name }}</option>@endforeach</select></label></div>
    <div><label>2 · Sector<select data-flow-sector disabled><option value="">Load institution first</option></select></label></div>
    <div><label>3 · Media<select data-flow-media disabled><option value="">Load sector first</option></select></label></div>
  </div>
  <div data-flow-display></div>
  <p class="rf-status" data-flow-status role="status">Start by choosing the institution responsible for the media.</p>
</section>
<script>
document.addEventListener('DOMContentLoaded',()=>{
const root=document.querySelector('[data-media-flow']);if(!root)return;
const inst=root.querySelector('[data-flow-institution'),sector=root.querySelector('[data-flow-sector'),media=root.querySelector('[data-flow-media'),display=root.querySelector('[data-flow-display'),status=root.querySelector('[data-flow-status');
let slug='';
function opts(sel,rows,ph){sel.replaceChildren(new Option(ph,''),...rows.map(r=>{const o=new Option(r.name,String(r.id));o.dataset.extra=JSON.stringify(r);return o;}));sel.disabled=!rows.length;}
inst.addEventListener('change',async()=>{const id=inst.value;slug=inst.selectedOptions[0]?.dataset.slug||'';opts(sector,[],'Loading…');opts(media,[],'Load sector first');display.replaceChildren();if(!id){opts(sector,[],'Load institution first');status.textContent='Choose an institution.';return;}
 status.textContent='Loading sectors…';
 const r=await fetch(root.dataset.sectorsUrl+'/'+id+'/sectors',{credentials:'same-origin',headers:{Accept:'application/json'}});const d=await r.json();
 opts(sector,d.sectors,'Choose sector');status.textContent=d.sectors.length+' sectors linked via sector_entities.';});
sector.addEventListener('change',async()=>{if(!slug)return;opts(media,[],'Loading…');
 status.textContent='Loading media and the algorithm pick…';
 const r=await fetch(root.dataset.sectorsUrl+'/'+inst.value+'/media?sector_id='+sector.value,{credentials:'same-origin',headers:{Accept:'application/json'}});const d=await r.json();
 opts(media,d.media.map(m=>({name:(m.slot||m.kind)+' · '+m.path.split('/').pop(),id:m.id})),'Choose media item');
 renderDisplay(d);status.textContent=d.media.length+' media items. Algorithm currently shows: '+d.display.state+'.';});
media.addEventListener('change',()=>{});
function renderDisplay(d){display.replaceChildren();
 const box=document.createElement('div');box.className='rf-media-current';
 const visual=d.display.video?document.createElement('video'):(d.display.image?document.createElement('img'):document.createElement('div'));
 if(d.display.video){visual.controls=true;visual.muted=true;visual.playsInline=true;visual.src=d.display.video;}
 else if(d.display.image){visual.src=d.display.image;visual.alt='current display';}
 else{visual.textContent='No verified media — the algorithm shows a placeholder.';visual.style.padding='30px';}
 const meta=document.createElement('div');meta.className='rf-meta';
 meta.innerHTML='<span class="rf-badge red">Algorithm pick</span> <span class="rf-badge">'+d.display.state+'</span><p style="margin-top:10px">'+d.display.reason+'</p>';
 const actions=document.createElement('div');actions.className='rf-actions';
 if(slug){const up=document.createElement('a');up.className='btn';up.href='/institution-admin/'+encodeURIComponent(slug)+'/videos';up.textContent='Open upload / replace / delete for this institution';actions.append(up);}
 meta.append(actions);box.append(visual,meta);display.append(box);}
});
</script>
@endsection
