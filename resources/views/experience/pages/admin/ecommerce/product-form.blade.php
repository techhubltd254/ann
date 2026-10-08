@extends('layouts.admin')
@section('title', $product->exists ? "Edit {$product->name}" : 'New Product')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">{{ $product->exists ? "Edit: {$product->name}" : 'New Product' }}</h1>

    <form method="POST" action="{{ $product->exists ? route('admin.ecommerce.product.update', $product->id) : route('admin.ecommerce.product.store') }}" class="bg-white rounded-lg shadow p-6">
        @csrf @if($product->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">Name *</label>
                <input name="name" value="{{ old('name', $product->name) }}" required class="border rounded w-full px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">SKU</label>
                <input name="sku" value="{{ old('sku', $product->sku) }}" class="border rounded w-full px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">County *</label>
                <select name="county_id" required class="border rounded w-full px-3 py-2">
                    @foreach($counties as $c) <option value="{{ $c->id }}" @selected(old('county_id', $product->county_id) == $c->id)>{{ $c->name }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Category *</label>
                <select name="category_id" required class="border rounded w-full px-3 py-2">
                    @foreach($categories as $c) <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Price</label>
                <input name="price" type="number" step="0.01" value="{{ old('price', $product->variants->min('price')) }}" class="border rounded w-full px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Compare At Price</label>
                <input name="compare_at_price" type="number" step="0.01" value="{{ old('compare_at_price', $product->variants->min('compare_at_price')) }}" class="border rounded w-full px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Unit</label>
                <input name="unit" value="{{ old('unit', $product->unit) }}" class="border rounded w-full px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Weight (kg)</label>
                <input name="weight_kg" type="number" step="0.01" value="{{ old('weight_kg', $product->weight_kg) }}" class="border rounded w-full px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select name="status" class="border rounded w-full px-3 py-2">
                    <option value="draft" @selected(($product->status ?? 'draft') === 'draft')>Draft</option>
                    <option value="active" @selected(($product->status ?? '') === 'active')>Active</option>
                    <option value="archived" @selected(($product->status ?? '') === 'archived')>Archived</option>
                </select>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Short Description</label>
            <input name="short_description" value="{{ old('short_description', $product->short_description) }}" class="border rounded w-full px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea name="description" rows="4" class="border rounded w-full px-3 py-2">{{ old('description', $product->description) }}</textarea>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Tags (comma separated)</label>
            <input name="tags" value="{{ old('tags', is_array($product->tags) ? implode(',', $product->tags) : $product->tags) }}" class="border rounded w-full px-3 py-2">
        </div>

        <div class="flex gap-4">
            <button class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">{{ $product->exists ? 'Update' : 'Create' }}</button>
            <a href="{{ route('admin.ecommerce.products') }}" class="bg-gray-200 px-6 py-2 rounded">Cancel</a>
        </div>
    </form>

    @if($product->exists && $product->variants->count() > 0)
    <div class="bg-white rounded-lg shadow p-6 mt-6">
        <h3 class="font-semibold mb-4">Variants</h3>
        <table class="w-full">
            <thead><tr><th class="p-1 text-left">Name</th><th class="p-1">SKU</th><th class="p-1">Price</th><th class="p-1">Stock</th><th class="p-1">Active</th></tr></thead>
            <tbody class="divide-y">
                @foreach($product->variants as $v)
                <tr><td class="p-1">{{ $v->name }}</td><td class="p-1 text-center">{{ $v->sku }}</td><td class="p-1 text-center">KES {{ number_format($v->price) }}</td><td class="p-1 text-center">{{ $v->stock }}</td><td class="p-1 text-center">{{ $v->is_active ? 'Yes' : 'No' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection