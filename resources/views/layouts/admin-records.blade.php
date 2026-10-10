@extends('layouts.nexora')
@section('title', 'KICC publishing admin')
@push('styles')
<style>
.ra-wrap{max-width:1180px;margin:0 auto;padding:32px 20px 80px;font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:#0B0B0B}
.ra-head{display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end;justify-content:space-between;margin-bottom:26px}
.ra-eyebrow{font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#0B0B0B;margin:0 0 8px}
.ra-h1{font-size:clamp(26px,3.4vw,40px);line-height:1.1;margin:0;font-weight:800;letter-spacing:-.02em}
.ra-h2{font-size:19px;margin:0 0 14px;font-weight:700}
.ra-h3{font-size:15px;margin:0 0 6px;font-weight:700}
.ra-muted{color:#0B0B0B;font-size:13px;margin:0}
.ra-grid{display:grid;gap:20px}
.ra-grid-2{grid-template-columns:repeat(auto-fit,minmax(320px,1fr))}
.ra-grid-3{grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
.ra-card{background:#FFFFFF;border:1px solid #FFFFFF;border-radius:14px;padding:20px}
.ra-card-dark{background:#FFFFFF;border-color:#0B0B0B;color:#FFFFFF}
.ra-nav{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid #FFFFFF}
.ra-nav a{font-size:13px;padding:7px 12px;border-radius:8px;text-decoration:none;color:#0B0B0B;background:#FFFFFF;border:1px solid transparent}
.ra-nav a:hover{background:#FFFFFF}
.ra-nav a[aria-current=page]{background:#FFFFFF;color:#FFFFFF}
.ra-table-wrap{overflow-x:auto;border:1px solid #FFFFFF;border-radius:12px;background:#FFFFFF}
.ra-table{width:100%;border-collapse:collapse;font-size:13px}
.ra-table th{text-align:left;padding:11px 14px;background:#FFFFFF;border-bottom:1px solid #FFFFFF;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#0B0B0B}
.ra-table td{padding:11px 14px;border-bottom:1px solid #FFFFFF;vertical-align:top}
.ra-table tr:last-child td{border-bottom:none}
.ra-btn{display:inline-block;font-size:13px;font-weight:600;padding:9px 16px;border-radius:9px;border:1px solid #0B0B0B;background:#FFFFFF;color:#FFFFFF;cursor:pointer;text-decoration:none}
.ra-btn:hover{opacity:.88}
.ra-btn-ghost{background:#FFFFFF;color:#0B0B0B;border-color:#FFFFFF}
.ra-btn-ghost:hover{background:#FFFFFF}
.ra-btn-danger{background:#FFFFFF;color:#B3261E;border-color:#B3261E}
.ra-btn-sm{font-size:11px;padding:5px 10px;border-radius:7px}
.ra-field{display:block;margin-bottom:14px;font-size:12px;font-weight:600;color:#0B0B0B}
.ra-field input,.ra-field select,.ra-field textarea{display:block;width:100%;margin-top:6px;padding:9px 11px;font-size:13px;font-weight:400;border:1px solid #FFFFFF;border-radius:8px;background:#FFFFFF;color:#0B0B0B;font-family:inherit}
.ra-field textarea{min-height:110px;resize:vertical}
.ra-fields{display:grid;gap:0 18px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
.ra-full{grid-column:1/-1}
.ra-status{font-size:12px;color:#0B0B0B}
.ra-pill{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:3px 8px;border-radius:999px;background:#FFFFFF;color:#0B0B0B}
.ra-pill-ok{background:#FFFFFF;color:#0B0B0B}
.ra-pill-draft{background:#FFCD05;color:#B3261E}
.ra-notice{background:#FFFFFF;border:1px solid #FFFFFF;border-left:3px solid #0B0B0B;border-radius:10px;padding:14px 16px;font-size:13px;color:#0B0B0B;margin:20px 0}
.ra-ok{background:#FFFFFF;border:1px solid #FFFFFF;color:#0B0B0B;border-radius:10px;padding:12px 15px;font-size:13px;margin-bottom:18px}
.ra-err{background:#B3261E;border:1px solid #B3261E;color:#B3261E;border-radius:10px;padding:12px 15px;font-size:13px;margin-bottom:18px}
.ra-media{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));margin-top:18px}
.ra-media-card{border:1px solid #FFFFFF;border-radius:12px;overflow:hidden;background:#FFFFFF}
.ra-media-card img,.ra-media-card video{display:block;width:100%;height:170px;object-fit:cover;background:#FFFFFF}
.ra-media-body{padding:13px}
.ra-media-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.ra-pager{display:flex;align-items:center;gap:12px;margin-top:20px;font-size:13px}
.ra-tree{font-size:13px}
.ra-tree-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 11px;border:1px solid #FFFFFF;border-radius:9px;margin-bottom:6px;background:#FFFFFF}
.ra-tree-item[aria-current=true]{border-color:#0B0B0B;background:#FFFFFF}
.ra-kpi{background:#FFFFFF;border:1px solid #FFFFFF;border-radius:12px;padding:14px 16px}
.ra-kpi b{display:block;font-size:24px;font-weight:800;letter-spacing:-.02em}
.ra-kpi span{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#0B0B0B}
.ra-cols{display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(280px,1fr))}
.ra-scroll{max-height:420px;overflow-y:auto}
</style>
@endpush
@section('content')
<div class="ra-wrap">
  <div class="ra-head">
    <div>
      <p class="ra-eyebrow">KICC · publishing control room</p>
      <h1 class="ra-h1">The public site starts here.</h1>
    </div>
    <a class="ra-btn ra-btn-ghost" href="/" target="_blank" rel="noopener noreferrer">Preview public site →</a>
  </div>

  <nav class="ra-nav" aria-label="Administration">
    <a href="{{ route('admin.index') }}" @if(request()->routeIs('admin.index')) aria-current="page" @endif>Overview</a>
    @foreach(config('kicc.types') as $type => $label)
      <a href="{{ route('admin.list', $type) }}" @if(request()->route('type') === $type) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
    <a href="{{ route('admin.hierarchy') }}" @if(request()->routeIs('admin.hierarchy')) aria-current="page" @endif>County → Sectors → Institutions</a>
    <a href="{{ route('admin.enquiries') }}" @if(request()->routeIs('admin.enquiries')) aria-current="page" @endif>Enquiries</a>
    <a href="{{ route('admin.audit') }}" @if(request()->routeIs('admin.audit')) aria-current="page" @endif>Audit history</a>
  </nav>

  @if(session('success'))<div class="ra-ok">{{ session('success') }}</div>@endif
  @if($errors->any())
    <div class="ra-err">
      @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
  @endif

  @yield('admin-content')
</div>
@endsection
