@extends('layouts.admin')
@section('title', 'Products')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Products ({{ $products->total() }})</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.ecommerce.import.form') }}" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Import</a>
            <a href="{{ route('admin.ecommerce.product.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Product</a>
        </div>
    </div>

    <form class="flex gap-2 mb-4 flex-wrap" method="GET">
        <input name="q" placeholder="Search..." value="{{ $filters['q'] ?? '' }}" class="border rounded px-3 py-1">
        <select name="category" class="border rounded px-3 py-1">
            <option value="">All Categories</option> @foreach($categories as $c) <option value="{{ $c->id }}" @selected(($filters['category'] ?? '') == $c->id)>{{ $c->name }}</option> @endforeach
        </select>
        <select name="county" class="border rounded px-3 py-1">
            <option value="">All Counties</option> @foreach($counties as $c) <option value="{{ $c->slug }}" @selected(($filters['county'] ?? '') == $c->slug)>{{ $c->name }}</option> @endforeach
        </select>
        <button class="bg-gray-200 px-4 py-1 rounded">Filter</button>
    </form>

    <form method="POST" action="{{ route('admin.ecommerce.product.bulk') }}">
        @csrf
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50"><tr><th class="p-2 text-left"><input type="checkbox" id="select-all"></th><th class="p-2 text-left">Product</th><th class="p-2">Category</th><th class="p-2">County</th><th class="p-2">Price</th><th class="p-2">Status</th><th class="p-2">Actions</th></tr></thead>
                <tbody class="divide-y">
                    @forelse($products as $p)
                    <tr>
                        <td class="p-2"><input type="checkbox" name="ids[]" value="{{ $p->id }}"></td>
                        <td class="p-2"><a href="{{ route('admin.ecommerce.product.edit', $p->id) }}" class="text-blue-600 hover:underline">{{ $p->name }}</a><br><span class="text-xs text-gray-500">{{ $p->variants->count() }} variants</span></td>
                        <td class="p-2 text-center">{{ $p->category?->name ?? '—' }}</td>
                        <td class="p-2 text-center">{{ $p->county?->name ?? '—' }}</td>
                        <td class="p-2 text-center">KES {{ number_format($p->variants->min('price') ?? 0) }}</td>
                        <td class="p-2 text-center"><span class="text-xs px-2 py-1 rounded @if($p->status === 'active') bg-green-100 text-green-800 @else bg-gray-100 @endif">{{ $p->status }}</span></td>
                        <td class="p-2 text-center">
                            <a href="{{ route('admin.ecommerce.product.edit', $p->id) }}" class="text-blue-600 text-sm">Edit</a>
                        </td>
                    </tr>
                    @empty <tr><td colspan="7" class="p-4 text-center text-gray-500">No products</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2 flex gap-2">
            <select name="action" class="border rounded px-3 py-1 text-sm">
                <option value="">Bulk Action</option>
                <option value="activate">Activate</option>
                <option value="draft">Set Draft</option>
                <option value="archive">Archive</option>
                <option value="feature">Feature</option>
                <option value="unfeature">Unfeature</option>
                <option value="delete">Delete</option>
            </select>
            <button class="bg-gray-200 px-3 py-1 rounded text-sm">Apply</button>
        </div>
    </form>
    <div class="mt-4">{{ $products->links() }}</div>
</div>
<script>document.getElementById('select-all')?.addEventListener('change', function() { document.querySelectorAll('input[name="ids[]"]').forEach(c => c.checked = this.checked); });</script>
@endsection