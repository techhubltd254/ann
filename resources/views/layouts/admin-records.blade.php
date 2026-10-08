@extends('layouts.app')
@section('title', 'KICC publishing admin')
@push('styles')
<style>
.ra-wrap{max-width:1180px;margin:0 auto;padding:32px 20px 80px;font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:#18181b}
.ra-head{display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end;justify-content:space-between;margin-bottom:26px}
.ra-eyebrow{font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#71717a;margin:0 0 8px}
.ra-h1{font-size:clamp(26px,3.4vw,40px);line-height:1.1;margin:0;font-weight:800;letter-spacing:-.02em}
.ra-h2{font-size:19px;margin:0 0 14px;font-weight:700}
.ra-h3{font-size:15px;margin:0 0 6px;font-weight:700}
.ra-muted{color:#71717a;font-size:13px;margin:0}
.ra-grid{display:grid;gap:20px}
.ra-grid-2{grid-template-columns:repeat(auto-fit,minmax(320px,1fr))}
.ra-grid-3{grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
.ra-card{background:#fff;border:1px solid #e4e4e7;border-radius:14px;padding:20px}
.ra-card-dark{background:#0d0d0f;border-color:#27272a;color:#fafafa}
.ra-nav{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid #e4e4e7}
.ra-nav a{font-size:13px;padding:7px 12px;border-radius:8px;text-decoration:none;color:#3f3f46;background:#f4f4f5;border:1px solid transparent}
.ra-nav a:hover{background:#e4e4e7}
.ra-nav a[aria-current=page]{background:#18181b;color:#fff}
.ra-table-wrap{overflow-x:auto;border:1px solid #e4e4e7;border-radius:12px;background:#fff}
.ra-table{width:100%;border-collapse:collapse;font-size:13px}
.ra-table th{text-align:left;padding:11px 14px;background:#fafafa;border-bottom:1px solid #e4e4e7;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#71717a}
.ra-table td{padding:11px 14px;border-bottom:1px solid #f4f4f5;vertical-align:top}
.ra-table tr:last-child td{border-bottom:none}
.ra-btn{display:inline-block;font-size:13px;font-weight:600;padding:9px 16px;border-radius:9px;border:1px solid #18181b;background:#18181b;color:#fff;cursor:pointer;text-decoration:none}
.ra-btn:hover{opacity:.88}
.ra-btn-ghost{background:#fff;color:#18181b;border-color:#d4d4d8}
.ra-btn-ghost:hover{background:#f4f4f5}
.ra-btn-danger{background:#fff;color:#b91c1c;border-color:#fecaca}
.ra-btn-sm{font-size:11px;padding:5px 10px;border-radius:7px}
.ra-field{display:block;margin-bottom:14px;font-size:12px;font-weight:600;color:#3f3f46}
.ra-field input,.ra-field select,.ra-field textarea{display:block;width:100%;margin-top:6px;padding:9px 11px;font-size:13px;font-weight:400;border:1px solid #d4d4d8;border-radius:8px;background:#fff;color:#18181b;font-family:inherit}
.ra-field textarea{min-height:110px;resize:vertical}
.ra-fields{display:grid;gap:0 18px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
.ra-full{grid-column:1/-1}
.ra-status{font-size:12px;color:#71717a}
.ra-pill{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:3px 8px;border-radius:999px;background:#f4f4f5;color:#52525b}
.ra-pill-ok{background:#dcfce7;color:#166534}
.ra-pill-draft{background:#fef3c7;color:#92400e}
.ra-notice{background:#fafafa;border:1px solid #e4e4e7;border-left:3px solid #18181b;border-radius:10px;padding:14px 16px;font-size:13px;color:#3f3f46;margin:20px 0}
.ra-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;border-radius:10px;padding:12px 15px;font-size:13px;margin-bottom:18px}
.ra-err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;padding:12px 15px;font-size:13px;margin-bottom:18px}
.ra-media{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));margin-top:18px}
.ra-media-card{border:1px solid #e4e4e7;border-radius:12px;overflow:hidden;background:#fff}
.ra-media-card img,.ra-media-card video{display:block;width:100%;height:170px;object-fit:cover;background:#f4f4f5}
.ra-media-body{padding:13px}
.ra-media-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.ra-pager{display:flex;align-items:center;gap:12px;margin-top:20px;font-size:13px}
.ra-tree{font-size:13px}
.ra-tree-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 11px;border:1px solid #e4e4e7;border-radius:9px;margin-bottom:6px;background:#fff}
.ra-tree-item[aria-current=true]{border-color:#18181b;background:#fafafa}
.ra-kpi{background:#fafafa;border:1px solid #e4e4e7;border-radius:12px;padding:14px 16px}
.ra-kpi b{display:block;font-size:24px;font-weight:800;letter-spacing:-.02em}
.ra-kpi span{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#71717a}
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
    <a class="ra-btn ra-btn-ghost" href="/">View public site →</a>
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
