@extends('layouts.nexora')
@section('title','Users & roles — KICC')
@section('content')
<x-experience.head eyebrow="People · role-based access" title="Who may administer what." lead="Roles are enforced by middleware on every administration route. A missing permission is not bypassed by a link." :stats="['accounts'=>$users->total(),'roles'=>$roles->count(),'permissions'=>$permissions->count()]" />
<section class="wrap admin-module">
  @if(session('status'))<p class="as-note" role="status">{{ session('status') }}</p>@endif
  <form method="get" class="admin-hub-toolbar"><label>Find an account<input type="search" name="q" value="{{ $q }}" placeholder="email or name"></label><button class="as-btn" type="submit">Search</button></form>
  <table class="as-table">
    <thead><tr><th>#</th><th>Account</th><th>Tier</th><th>Roles</th><th>Assign</th></tr></thead>
    <tbody>
    @foreach($users as $u)
      <tr>
        <td>{{ $u->id }}</td>
        <td>{{ $u->email }}<br><small>{{ $u->name }}</small></td>
        <td>{{ $u->tier ?: '—' }}{{ $u->is_admin ? ' · flagged admin' : '' }}</td>
        <td>{{ $u->roles->pluck('name')->implode(', ') ?: 'none' }}</td>
        <td>
          <form method="post" action="{{ route('admin.users.roles', $u) }}">@csrf
            @foreach($roles as $r)
              <label class="as-chip"><input type="checkbox" name="roles[]" value="{{ $r }}" @checked($u->hasRole($r))> {{ $r }}</label>
            @endforeach
            <button class="as-btn" type="submit">Save roles</button>
          </form>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <div class="as-pager">{{ $users->links() }}</div>
  <p class="as-note">Role counts: @foreach($roleCounts as $r=>$c){{ $r }}={{ $c }} @endforeach</p>
</section>
@endsection
