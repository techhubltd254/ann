@extends('layouts.nexora')
@section('title','Administration — KICC')
@section('content')
<x-experience.head eyebrow="One control centre · existing backend" title="Administration, connected." lead="The original administration panels, their real controls and the county → sector → institution hierarchy — together in the experience interface." :stats="['your access'=>$level,'counties'=>$counties->count(),'institutions'=>$institutions->count(),'registered routes'=>count($routes)]" />
<section class="wrap admin-hub" data-admin-hub>
  <div class="admin-hub-toolbar"><label>Find a control<input type="search" data-admin-search placeholder="Videos, sectors, orders, publishing…"></label><p>Access is checked on the server. A missing permission is not bypassed by these links.</p></div>
  @if($tools)<section class="admin-module" data-admin-module><h2>Mother / KICC · old and new controls</h2><div class="admin-link-grid">@foreach($tools as $tool)<a class="admin-control-link" href="{{ $tool['url'] }}">{{ $tool['label'] }}<span aria-hidden="true">↗</span></a>@endforeach</div></section>@endif
  @foreach(['kicc'=>'Mother / KICC','national'=>'National government'] as $key=>$label)
    @if(isset($modules[$key]))<section class="admin-module" data-admin-module><h2>{{ $label }} <small>{{ count($modules[$key]['tabs']) }} native tabs</small></h2><div class="admin-link-grid">@foreach($modules[$key]['tabs'] as $tab)<a class="admin-control-link" data-native-tab="{{ $key }}:{{ $tab['tab'] }}" href="{{ route($modules[$key]['route'],['tab'=>$tab['tab']]) }}">{{ $tab['label'] }}<span aria-hidden="true">↗</span></a>@endforeach</div></section>@endif
  @endforeach
  <section class="admin-module" data-admin-module><h2>County → Sector → Institution</h2><p>Uses the existing county_sector pivot and sector_entities links. No invented parent relationships.</p>
    <div class="admin-hierarchy" data-hierarchy-url="{{ route('admin.hub.hierarchy') }}">
      <label>County<select data-hierarchy-county><option value="">Choose county</option>@foreach($counties as $county)<option value="{{ $county->id }}" data-slug="{{ $county->slug }}">{{ $county->name }}</option>@endforeach</select></label>
      <label>Sector<select data-hierarchy-sector disabled><option value="">All linked sectors</option></select></label>
      <label>Institution<select data-hierarchy-institution disabled><option value="">Choose institution</option></select></label>
      <div class="admin-hierarchy-actions"><a class="btn ghost" data-open-county hidden>Open county admin</a><a class="btn" data-open-institution hidden>Open institution admin</a></div>
      <p data-hierarchy-status role="status">Choose a county to load its real hierarchy.</p>
    </div>
  </section>
  @if(isset($modules['county']))<section class="admin-module" data-admin-module><h2>County administration <small>{{ count($modules['county']['tabs']) }} tabs per county</small></h2>
    @foreach($counties as $county)<details class="admin-entity"><summary>{{ $county->name }} <span>{{ $county->sectors_count }} linked sectors</span></summary><div class="admin-link-grid">@foreach($modules['county']['tabs'] as $tab)<a class="admin-control-link" data-native-tab="county:{{ $tab['tab'] }}" href="{{ route('county.admin.pro',[$county->slug,'tab'=>$tab['tab']]) }}">{{ $tab['label'] }}</a>@endforeach</div></details>@endforeach
  </section>@endif
  @if(isset($modules['institution']))<section class="admin-module" data-admin-module><h2>Institution administration <small>{{ count($modules['institution']['tabs']) }} tabs per institution</small></h2>
    @forelse($institutions as $institution)<details class="admin-entity"><summary>{{ $institution->name }} <span>{{ $institution->county?->name }}</span></summary><div class="admin-link-grid">@foreach($modules['institution']['tabs'] as $tab)<a class="admin-control-link" data-native-tab="institution:{{ $tab['tab'] }}" href="{{ route('institution.admin',[$institution->slug,'tab'=>$tab['tab']]) }}">{{ $tab['label'] }}</a>@endforeach</div></details>@empty<p>No institution is assigned to this account.</p>@endforelse
  </section>@endif
  <details class="admin-module"><summary>Registered backend operations · {{ count($routes) }} routes</summary><p>{{ $legacyCount }} entries in the preserved legacy function registry. Registered does not mean every production mutation has been tested.</p><div class="admin-route-table"><table><thead><tr><th>Method</th><th>Existing route</th><th>Handler</th></tr></thead><tbody>@foreach($routes as $row)<tr><td>{{ implode(' / ',array_diff($row['methods'],['HEAD'])) }}</td><td>{{ $row['uri'] }}</td><td>{{ $row['action'] }}</td></tr>@endforeach</tbody></table></div></details>
</section>
@endsection
