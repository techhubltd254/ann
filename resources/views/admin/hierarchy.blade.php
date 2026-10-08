@extends('layouts.admin-records')
@section('admin-content')
<div class="ra-head" style="margin-bottom:18px">
  <div>
    <h2 class="ra-h2" style="margin:0">County → Sectors → Institutions</h2>
    <p class="ra-muted">Built on the legacy records model: one <code>records</code> table, linked by <code>parent_id</code>, with the native row id in <code>payload.source_id</code>.</p>
  </div>
  <form method="POST" action="{{ route('admin.hierarchy.mirror') }}">
    @csrf
    <button class="ra-btn">Mirror native hierarchy</button>
  </form>
</div>

<div class="ra-grid ra-grid-3" style="margin-bottom:24px">
  <div class="ra-kpi"><b>{{ $nativeCounts['counties'] }}</b><span>Native counties</span></div>
  <div class="ra-kpi"><b>{{ $nativeCounts['sectors'] }}</b><span>Native sectors</span></div>
  <div class="ra-kpi"><b>{{ $nativeCounts['institutions'] }}</b><span>Native institutions</span></div>
  <div class="ra-kpi"><b>{{ $nativeCounts['sector_entities'] }}</b><span>Native sector entities</span></div>
  <div class="ra-kpi"><b>{{ $mirrored['counties'] }} / {{ $mirrored['sectors'] }} / {{ $mirrored['institutions'] }}</b><span>Mirrored records (county/sector/inst)</span></div>
</div>

<div class="ra-cols">
  <div class="ra-card">
    <h3 class="ra-h3">1 · Counties</h3>
    <p class="ra-muted" style="margin-bottom:10px">{{ $counties->count() }} records</p>
    <div class="ra-scroll">
      @forelse($counties as $c)
        <a class="ra-tree-item" aria-current="{{ $selectedCounty && $selectedCounty->id === $c->id ? 'true' : 'false' }}"
           href="{{ route('admin.hierarchy', ['county' => $c->id]) }}">
          <span>{{ $c->name }}</span><span class="ra-pill">{{ $c->status }}</span>
        </a>
      @empty
        <p class="ra-muted">No county records yet — run “Mirror native hierarchy”.</p>
      @endforelse
    </div>
  </div>

  <div class="ra-card">
    <h3 class="ra-h3">2 · Sectors @if($selectedCounty) in {{ $selectedCounty->name }} @endif</h3>
    <p class="ra-muted" style="margin-bottom:10px">{{ $sectors->count() }} linked</p>
    <div class="ra-scroll">
      @forelse($sectors as $s)
        <div class="ra-tree-item" aria-current="{{ $selectedSector && $selectedSector->id === $s->id ? 'true' : 'false' }}">
          <a href="{{ route('admin.hierarchy', ['county' => $selectedCounty->id, 'sector' => $s->id]) }}">{{ $s->name }}</a>
          <form method="POST" action="{{ route('admin.hierarchy.unlink') }}">
            @csrf<input type="hidden" name="child_id" value="{{ $s->id }}">
            <button class="ra-btn ra-btn-ghost ra-btn-sm">Detach</button>
          </form>
        </div>
      @empty
        <p class="ra-muted">No sectors linked to this county yet.</p>
      @endforelse
    </div>
    @if($selectedCounty)
      <form method="POST" action="{{ route('admin.hierarchy.link') }}" style="margin-top:14px">
        @csrf
        <input type="hidden" name="parent_id" value="{{ $selectedCounty->id }}">
        <label class="ra-field">Attach an unplaced sector
          <select name="child_id" required>
            <option value="">Select sector…</option>
            @foreach($unplacedSectors as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
          </select>
        </label>
        <button class="ra-btn ra-btn-ghost ra-btn-sm">Attach sector</button>
      </form>
      <form method="POST" action="{{ route('admin.hierarchy.create') }}" style="margin-top:10px">
        @csrf
        <input type="hidden" name="type" value="sectors">
        <input type="hidden" name="parent_id" value="{{ $selectedCounty->id }}">
        <label class="ra-field">New sector in {{ $selectedCounty->name }}
          <input name="name" required placeholder="Sector name">
        </label>
        <button class="ra-btn ra-btn-ghost ra-btn-sm">Create sector</button>
      </form>
    @endif
  </div>

  <div class="ra-card">
    <h3 class="ra-h3">3 · Institutions @if($selectedSector) in {{ $selectedSector->name }} @endif</h3>
    <p class="ra-muted" style="margin-bottom:10px">{{ $institutions->count() }} linked</p>
    <div class="ra-scroll">
      @forelse($institutions as $i)
        <div class="ra-tree-item">
          <span>{{ $i->name }}<br><span class="ra-status">{{ $i->payload['type'] ?? '' }}</span></span>
          <span style="display:flex;gap:6px;align-items:center">
            <span class="ra-pill {{ $i->status === 'published' ? 'ra-pill-ok' : 'ra-pill-draft' }}">{{ $i->status }}</span>
            <a class="ra-btn ra-btn-ghost ra-btn-sm" href="{{ route('admin.edit', $i) }}">Edit</a>
            <form method="POST" action="{{ route('admin.hierarchy.unlink') }}">
              @csrf<input type="hidden" name="child_id" value="{{ $i->id }}">
              <button class="ra-btn ra-btn-ghost ra-btn-sm">Detach</button>
            </form>
          </span>
        </div>
      @empty
        <p class="ra-muted">No institutions linked to this sector yet.</p>
      @endforelse
    </div>
    @if($selectedSector)
      <form method="POST" action="{{ route('admin.hierarchy.link') }}" style="margin-top:14px">
        @csrf
        <input type="hidden" name="parent_id" value="{{ $selectedSector->id }}">
        <label class="ra-field">Attach an unplaced institution
          <select name="child_id" required>
            <option value="">Select institution…</option>
            @foreach($unplacedInstitutions as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
          </select>
        </label>
        <button class="ra-btn ra-btn-ghost ra-btn-sm">Attach institution</button>
      </form>
      <form method="POST" action="{{ route('admin.hierarchy.create') }}" style="margin-top:10px">
        @csrf
        <input type="hidden" name="type" value="institutions">
        <input type="hidden" name="parent_id" value="{{ $selectedSector->id }}">
        <label class="ra-field">New institution in {{ $selectedSector->name }}
          <input name="name" required placeholder="Institution name">
        </label>
        <button class="ra-btn ra-btn-ghost ra-btn-sm">Create institution</button>
      </form>
    @endif
  </div>
</div>

<div class="ra-notice">
  The native <code>counties</code>, <code>sectors</code>, <code>county_institutions</code> and <code>sector_entities</code> tables remain the source of truth for the public site.
  This screen mirrors them into publishing records and manages the county → sector → institution tree.
  Mirroring is idempotent — re-running never duplicates a node.
</div>
@endsection
