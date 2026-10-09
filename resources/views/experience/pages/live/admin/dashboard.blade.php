@extends('layouts.admin-theme', ['title' => 'Live Admin', 'themeColor' => '#6366F1', 'brandName' => 'Live Admin', 'brandSub' => 'KICC Streams'])
@section('title', 'Live Platform Dashboard — Super Admin')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold" style="color: var(--kicc-navy);">Live Platform Dashboard</h1>
        <span class="badge-kicc-green px-3 py-1 rounded-full text-sm">{{ $stats['live_now'] }} Live Now</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        <div class="card-kicc p-4"><div class="text-sm text-gray-500">Total Booths</div><div class="text-2xl font-bold">{{ $stats['total_booths'] }}</div></div>
        <div class="card-kicc p-4"><div class="text-sm text-gray-500">Authorized</div><div class="text-2xl font-bold text-green-600">{{ $stats['authorized'] }}</div></div>
        <div class="card-kicc p-4"><div class="text-sm text-gray-500">Live Now</div><div class="text-2xl font-bold text-red-600">{{ $stats['live_now'] }}</div></div>
        <div class="card-kicc p-4"><div class="text-sm text-gray-500">Heartbeat Health</div><div class="text-2xl font-bold">{{ $stats['heartbeat_health'] }}</div></div>
        <div class="card-kicc p-4"><div class="text-sm text-gray-500">Capacity Used</div><div class="text-2xl font-bold">{{ $stats['broker_metrics']['capacity_used_percent'] ?? 0 }}%</div></div>
    </div>

    <div class="card-kicc">
        <div class="p-4 border-b border-white/10 flex justify-between items-center">
            <h2 class="text-lg font-semibold">All Booths</h2>
            <a href="{{ route('live.admin.monitor') }}" class="btn-kicc-primary px-4 py-2 rounded-lg text-sm">Multiview Monitor</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-gray-50 text-left">
                    <th class="p-3 font-medium">Booth</th><th class="p-3 font-medium">Status</th><th class="p-3 font-medium">Auth</th><th class="p-3 font-medium">Heartbeat</th><th class="p-3 font-medium">Actions</th>
                </tr></thead>
                <tbody>
                @foreach($booths as $booth)
                <tr class="border-t hover:bg-gray-50">
                    <td class="p-3 font-medium">{{ $booth->name }}</td>
                    <td class="p-3">
                        <span class="px-2 py-0.5 rounded-full text-xs {{
                            $booth->stream_status === 'live' ? 'badge-kicc-green' : ($booth->stream_status === 'paused' ? 'badge-kicc-gold' : 'bg-gray-100 text-gray-600')
                        }}">{{ $booth->stream_status ?? 'offline' }}</span>
                    </td>
                    <td class="p-3">{{ $booth->authorization?->status ?? 'NONE' }}</td>
                    <td class="p-3">
                        <span class="inline-block w-2 h-2 rounded-full mr-1 {{
                            $booth->authorization?->last_heartbeat_at && $booth->authorization->last_heartbeat_at->diffInSeconds(now()) < 15 ? 'bg-green-500' : 'bg-red-500'
                        }}"></span>
                        {{ $booth->authorization?->last_heartbeat_at?->diffForHumans() ?? '--' }}
                    </td>
                    <td class="p-3 space-x-2">
                        @if(!$booth->authorization || $booth->authorization->status !== 'AUTHORIZED')
                        <form action="{{ route('live.admin.authorize', $booth) }}" method="POST" class="inline">
                            @csrf<button class="text-green-600 hover:text-green-800 text-xs font-medium">Authorize</button>
                        </form>
                        @endif
                        @if($booth->authorization && $booth->authorization->status === 'AUTHORIZED')
                        <form action="{{ route('live.admin.terminate', $booth) }}" method="POST" class="inline" onsubmit="return confirm('Terminate this booth?')">
                            @csrf<button class="text-red-600 hover:text-red-800 text-xs font-medium">Terminate</button>
                        </form>
                        <form action="{{ route('live.admin.api-key', $booth) }}" method="POST" class="inline">
                            @csrf<button class="text-blue-600 hover:text-blue-800 text-xs font-medium">API Key</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection