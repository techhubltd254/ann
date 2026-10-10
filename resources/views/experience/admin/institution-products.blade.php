@extends('layouts.nexora')
@section('title', $institution->name.' · Products')
@section('content')
<section class="as-card"><span class="as-eyebrow">Institution → Products</span><h1>{{ $institution->name }}</h1><p>County: {{ $institution->county?->name }}. Every edit below targets a stored product ID belonging to this institution.</p><p><a class="as-cta" href="{{ route('institution.admin',[$institution->slug,'tab'=>'overview']) }}">Institution dashboard</a></p></section>
<section class="as-card"><table class="w-full"><thead><tr><th>Product</th><th>Price</th><th>Published videos</th><th>Administration</th></tr></thead><tbody>@forelse($products as $p)<tr><td>{{ $p->name }}</td><td>KES {{ number_format($p->price??0,2) }}</td><td>{{ count($p->videos??[]) }}</td><td><a class="as-cta" href="{{ route('institution.products.edit',[$institution->slug,$p->id]) }}">Edit product & videos</a></td></tr>@empty<tr><td colspan="4">No products linked to this institution. Use the institution dashboard to create one.</td></tr>@endforelse</tbody></table>{{ $products->links() }}</section>
@endsection
