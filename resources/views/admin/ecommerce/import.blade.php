@extends('layouts.admin')
@section('title', 'Import Products')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Import Products</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-semibold text-lg mb-4">Quick Import</h2>
            <form method="POST" action="{{ route('admin.ecommerce.import.run') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Source</label>
                    <select name="source" class="border rounded w-full px-3 py-2">
                        @foreach($sources as $s) <option value="{{ $s }}">{{ ucfirst($s) }} {{ $s !== 'kicc' ? '(mock)' : '' }}</option> @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Search Query</label>
                    <input name="query" class="border rounded w-full px-3 py-2" placeholder="e.g., coffee, textiles, crafts" value="{{ old('query') }}" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Products to Import</label>
                    <input name="limit" type="number" min="1" max="50" value="10" class="border rounded w-full px-3 py-2">
                </div>
                <button class="bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700">Import Now</button>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-semibold text-lg mb-4">Bulk Import (JSON)</h2>
            <p class="text-sm text-gray-600 mb-4">Paste a JSON array of products for bulk import.</p>
            <form method="POST" action="{{ route('admin.ecommerce.import.bulk') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">JSON Data</label>
                    <textarea name="products_json" rows="10" class="border rounded w-full px-3 py-2 font-mono text-sm" placeholder='[{"name":"Product Name","price":1000,"description":"...","county_id":1,"category_id":1}]'></textarea>
                </div>
                <button class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Bulk Import</button>
            </form>
            <div class="mt-4 text-xs text-gray-500">
                <p>Required fields: name, price, county_id, category_id</p>
                <p>Optional: description, image_url, stock, sku, compare_at_price, tags[]</p>
                <p>Get county_ids: <code>/api/counties</code></p>
                <p>Get category_ids: <code>/api/marketplace/categories</code></p>
            </div>
        </div>
    </div>
</div>
@endsection