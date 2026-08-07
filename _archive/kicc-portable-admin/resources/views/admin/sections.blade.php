@extends('layouts.app')
@section('title', 'Admin Sections')
@section('content')
<style>
[x-cloak]{display:none}
</style>
<div class="p-6 lg:p-8" x-data="{ tab: 'exhibitions' }">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-black text-white">System Administration</h1>
            <p class="text-white/30 text-sm mt-1">Manage all platform sections</p>
        </div>
        <a href="/kicc-admin" class="text-xs text-[#FFCD05] hover:underline">&larr; Back to Dashboard</a>
    </div>

    <div class="flex gap-1 mb-6 overflow-x-auto flex-wrap border-b border-white/8 pb-3">
        @foreach([
            ['id'=>'exhibitions','label'=>'Exhibitions','icon'=>'📅','count'=>$exhibitions->count()],
            ['id'=>'venues','label'=>'Venues','icon'=>'🏢','count'=>$venues->count()],
            ['id'=>'booths','label'=>'Booths','icon'=>'🎪','count'=>$booths->count()],
            ['id'=>'screens','label'=>'Screens','icon'=>'🖥️','count'=>$screens->count()],
            ['id'=>'plans','label'=>'Subscription Plans','icon'=>'💳','count'=>$subscriptionPlans->count()],
            ['id'=>'subscribers','label'=>'Subscribers','icon'=>'👥','count'=>$subscribers->count()],
            ['id'=>'users','label'=>'Users','icon'=>'👤','count'=>$users->count()],
            ['id'=>'sync','label'=>'Sync','icon'=>'🔄','count'=>''],
        ] as $t)
        <button @click="tab='{{ $t['id'] }}'"
            class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0"
            :class="tab==='{{ $t['id'] }}' ? 'bg-[#901C1E]/15 text-[#FFCD05] border border-[#901C1E]/30' : 'text-white/40 border border-transparent hover:text-white hover:bg-white/5'">
            <span>{{ $t['icon'] }}</span> {{ $t['label'] }}
            <span class="text-[10px] text-white/30">({{ $t['count'] }})</span>
        </button>
        @endforeach
    </div>

    {{-- ═══ EXHIBITIONS ═══ --}}
    <div x-show="tab==='exhibitions'" x-cloak>
        @include('admin.partials.section-table', [
            'title'=>'Exhibitions', 'items'=>$exhibitions,
            'fields'=>['Name'=>'name','Slug'=>'slug','Start'=>'start_date','End'=>'end_date','Organizer'=>'organizer','Status'=>'status'],
            'storeRoute'=>'admin.exhibitions.store', 'deleteRoute'=>'admin.exhibitions.delete',
            'formFields'=>[
                ['name'=>'name','label'=>'Name','type'=>'text','required'=>true],
                ['name'=>'slug','label'=>'Slug','type'=>'text','required'=>true],
                ['name'=>'start_date','label'=>'Start Date','type'=>'date','required'=>true],
                ['name'=>'end_date','label'=>'End Date','type'=>'date','required'=>true],
                ['name'=>'organizer','label'=>'Organizer','type'=>'text'],
                ['name'=>'venue_id','label'=>'Venue ID','type'=>'number'],
                ['name'=>'status','label'=>'Status','type'=>'select','options'=>['draft'=>'Draft','published'=>'Published','cancelled'=>'Cancelled']],
            ]
        ])
    </div>

    {{-- ═══ VENUES ═══ --}}
    <div x-show="tab==='venues'" x-cloak>
        @include('admin.partials.section-table', [
            'title'=>'Venues', 'items'=>$venues,
            'fields'=>['Name'=>'name','Slug'=>'slug','Capacity'=>'capacity','Type'=>'type','Location'=>'location'],
            'storeRoute'=>'admin.venues.store', 'deleteRoute'=>'admin.venues.delete',
            'formFields'=>[
                ['name'=>'name','label'=>'Name','type'=>'text','required'=>true],
                ['name'=>'slug','label'=>'Slug','type'=>'text','required'=>true],
                ['name'=>'capacity','label'=>'Capacity','type'=>'number'],
                ['name'=>'type','label'=>'Type','type'=>'text'],
                ['name'=>'location','label'=>'Location','type'=>'text'],
                ['name'=>'price_per_hour','label'=>'Price/Hour (KSh)','type'=>'number'],
            ]
        ])
    </div>

    {{-- ═══ BOOTHS ═══ --}}
    <div x-show="tab==='booths'" x-cloak>
        @include('admin.partials.section-table', [
            'title'=>'Exhibition Booths', 'items'=>$booths,
            'fields'=>['Name'=>'name','Size'=>'size','Price'=>'price','Max Qty'=>'max_quantity'],
            'storeRoute'=>'admin.booths.store', 'deleteRoute'=>'admin.booths.delete',
            'formFields'=>[
                ['name'=>'name','label'=>'Name','type'=>'text','required'=>true],
                ['name'=>'venue_id','label'=>'Venue ID','type'=>'number'],
                ['name'=>'size','label'=>'Size','type'=>'text'],
                ['name'=>'price','label'=>'Price (KSh)','type'=>'number'],
                ['name'=>'max_quantity','label'=>'Max Quantity','type'=>'number'],
            ]
        ])
    </div>

    {{-- ═══ SCREENS ═══ --}}
    <div x-show="tab==='screens'" x-cloak>
        @include('admin.partials.section-table', [
            'title'=>'Digital Screens', 'items'=>$screens,
            'fields'=>['Name'=>'name','Location'=>'location','Type'=>'type','Resolution'=>'resolution','Status'=>'status'],
            'storeRoute'=>'admin.screens.store', 'deleteRoute'=>'admin.screens.delete',
            'formFields'=>[
                ['name'=>'name','label'=>'Name','type'=>'text','required'=>true],
                ['name'=>'county_id','label'=>'County ID','type'=>'number'],
                ['name'=>'location','label'=>'Location','type'=>'text'],
                ['name'=>'type','label'=>'Type','type'=>'text'],
                ['name'=>'resolution','label'=>'Resolution','type'=>'text'],
                ['name'=>'status','label'=>'Status','type'=>'select','options'=>['active'=>'Active','inactive'=>'Inactive']],
            ]
        ])
    </div>

    {{-- ═══ SUBSCRIPTION PLANS ═══ --}}
    <div x-show="tab==='plans'" x-cloak>
        @include('admin.partials.section-table', [
            'title'=>'Subscription Plans', 'items'=>$subscriptionPlans,
            'fields'=>['Name'=>'name','Price'=>'price','Interval'=>'billing_interval','Active'=>'is_active'],
            'storeRoute'=>'admin.plans.store', 'deleteRoute'=>'admin.plans.delete',
            'formFields'=>[
                ['name'=>'name','label'=>'Name','type'=>'text','required'=>true],
                ['name'=>'slug','label'=>'Slug','type'=>'text','required'=>true],
                ['name'=>'price','label'=>'Price (KSh)','type'=>'number','required'=>true],
                ['name'=>'billing_interval','label'=>'Billing Interval','type'=>'select','options'=>['monthly'=>'Monthly','yearly'=>'Yearly','quarterly'=>'Quarterly'],'required'=>true],
                ['name'=>'description','label'=>'Description','type'=>'textarea'],
                ['name'=>'features','label'=>'Features (comma-separated)','type'=>'text'],
            ]
        ])
    </div>

    {{-- ═══ SUBSCRIBERS ═══ --}}
    <div x-show="tab==='subscribers'" x-cloak>
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/8">
                <h3 class="font-bold text-white text-sm">Recent Subscribers ({{ $subscribers->count() }})</h3>
            </div>
            @if($subscribers->count())
            <table class="w-full">
                <thead><tr class="bg-[#141B2E]">
                    <th class="text-[10px] font-bold text-white/30 uppercase px-4 py-3 text-left">Name</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase px-4 py-3 text-left">Email</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase px-4 py-3 text-left">County</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase px-4 py-3 text-left">Status</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase px-4 py-3 text-right">Date</th>
                </tr></thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($subscribers as $s)
                    <tr class="hover:bg-white/2">
                        <td class="px-4 py-3 text-sm text-white">{{ $s->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-white/50">{{ $s->email ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-white/50">{{ $s->county?->name ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="badge-active">{{ $s->status ?? 'active' }}</span></td>
                        <td class="px-4 py-3 text-sm text-white/30 text-right">{{ $s->created_at ? $s->created_at->format('d M Y') : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center py-12 text-white/30">
                <div class="text-3xl mb-2">👥</div>
                <p class="text-sm">No subscribers yet</p>
            </div>
            @endif
        </div>
    </div>

    {{-- ═══ USERS ═══ --}}
    <div x-show="tab==='users'" x-cloak>
        <div class="bg-[#0D1220] border border-white/8 rounded-2xl overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/8">
                <h3 class="font-bold text-white text-sm">Platform Users ({{ $users->count() }})</h3>
                <button @click="document.getElementById('user-form').classList.toggle('hidden')" class="bg-[#901C1E] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#7b1618]">+ Add User</button>
            </div>
            <div id="user-form" class="hidden bg-[#141B2E] p-6 border-b border-white/8">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Name</label><input name="name" required class="w-full bg-[#0D1220] border border-white/10 rounded-xl px-4 py-2 text-sm text-white"></div>
                        <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Email</label><input type="email" name="email" required class="w-full bg-[#0D1220] border border-white/10 rounded-xl px-4 py-2 text-sm text-white"></div>
                        <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Password</label><input type="password" name="password" required class="w-full bg-[#0D1220] border border-white/10 rounded-xl px-4 py-2 text-sm text-white"></div>
                        <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">Role</label>
                            <select name="role" required class="w-full bg-[#0D1220] border border-white/10 rounded-xl px-4 py-2 text-sm text-white">
                                @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select></div>
                    </div>
                    <button class="mt-4 bg-[#901C1E] text-white px-6 py-2 rounded-xl text-sm font-bold hover:bg-[#7b1618]">Save User</button>
                </form>
            </div>
            @if($users->count())
            <table class="w-full">
                <thead><tr class="bg-[#141B2E]">
                    <th class="text-[10px] font-bold text-white/30 uppercase px-4 py-3 text-left">Name</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase px-4 py-3 text-left">Email</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase px-4 py-3 text-left">Roles</th>
                    <th class="text-[10px] font-bold text-white/30 uppercase px-4 py-3 text-right">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($users as $u)
                    <tr class="hover:bg-white/2">
                        <td class="px-4 py-3 text-sm text-white">{{ $u->name }}</td>
                        <td class="px-4 py-3 text-sm text-white/50">{{ $u->email }}</td>
                        <td class="px-4 py-3 text-sm text-white/50">{{ $u->roles->pluck('name')->implode(', ') }}</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.users.delete', $u->id) }}" onsubmit="return confirm('Delete {{ $u->name }}?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-white/30 hover:text-[#901C1E] font-bold">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center py-12 text-white/30"><div class="text-3xl mb-2">👤</div><p class="text-sm">No users</p></div>
            @endif
        </div>
    </div>

    {{-- ═══ SYNC ═══ --}}
    <div x-show="tab==='sync'" x-cloak>
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-6">
                    <span class="text-2xl">📥</span>
                    <div><h3 class="font-bold text-white">Pull from TiDB Cloud</h3><p class="text-xs text-white/30">Download latest production data</p></div>
                </div>
                <form method="POST" action="{{ route('admin.sync.pull') }}">
                    @csrf
                    <button class="w-full bg-[#0B1E57] text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-[#0D2A7A] transition-all">Pull All Data</button>
                </form>
            </div>
            <div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-6">
                    <span class="text-2xl">📤</span>
                    <div><h3 class="font-bold text-white">Push to TiDB Cloud</h3><p class="text-xs text-white/30">Upload local changes to production</p></div>
                </div>
                <form method="POST" action="{{ route('admin.sync.push') }}">
                    @csrf
                    <button class="w-full bg-[#901C1E] text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-[#7b1618] transition-all">Push Local Changes</button>
                </form>
            </div>
        </div>
        @if(session('sync_output'))
        <div class="mt-6 bg-[#0D1220] border border-white/8 rounded-2xl p-6">
            <h4 class="font-bold text-white text-xs mb-3">Sync Output</h4>
            <pre class="text-xs text-white/50 font-mono whitespace-pre-wrap">{{ session('sync_output') }}</pre>
        </div>
        @endif
    </div>
</div>
@endsection
