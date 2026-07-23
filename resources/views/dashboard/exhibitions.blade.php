@extends('layouts.app')

@section('title', 'My Exhibitions')
@section('description', 'Manage your exhibitions on KICC.')

@section('content')
<div class="bg-gradient-to-br from-amber-50 to-orange-100 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('dashboard.index') }}" class="text-amber-600 hover:text-amber-700 mb-4 inline-block">&larr; Dashboard</a>
        <h1 class="text-4xl font-bold text-gray-900 mb-2">My Exhibitions</h1>
        <p class="text-lg text-gray-600">Exhibitions you've organized or manage.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    @if($exhibitions->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-sm font-semibold text-gray-600">Name</th>
                    <th class="text-left px-6 py-3 text-sm font-semibold text-gray-600">Dates</th>
                    <th class="text-left px-6 py-3 text-sm font-semibold text-gray-600">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($exhibitions as $exhibition)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <a href="{{ route('exhibitions.show', $exhibition->slug) }}" class="font-medium text-amber-600 hover:text-amber-700">{{ $exhibition->name }}</a>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $exhibition->start_date->format('M d, Y') }} - {{ $exhibition->end_date->format('M d, Y') }}</td>
                    <td class="px-6 py-4">
                        <span class="text-xs font-medium px-2 py-1 rounded {{ $exhibition->status === 'published' ? 'bg-green-50 text-green-600' : 'bg-gray-50 text-gray-500' }}">
                            {{ ucfirst($exhibition->status) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $exhibitions->links() }}</div>
    @else
    <div class="text-center py-16 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="text-5xl mb-4">🏛️</div>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No exhibitions yet</h3>
        <p class="text-gray-500">Contact the admin to create your first exhibition.</p>
    </div>
    @endif
</div>
@endSection
