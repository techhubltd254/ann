@extends('layouts.experience-live')
@section('title','Marketplace — KICC')
@section('content')
<x-experience.head
  eyebrow="What the counties send to market"
  title="Golden Vortex Product Grid"
  lead="Every listing belongs to a county and an institution. Filter by county or category and open the ones you want to trade."
  :stats="['listings' => number_format($products->total()), 'counties' => $counties->count()]"
/>

<section class="ex-page"><div class="wrap">
  <div class="ex-bar">
    <form method="GET" action="{{ route('marketplace.index') }}">
      <input type="search" name="q" value="{{ $q }}" placeholder="Search products, producers, sectors" aria-label="Search marketplace">
      <select name="county" aria-label="County">
        <option value="">All counties</option>
        @foreach($counties as $c)
          <option value="{{ $c->slug ?? $c->name }}" @selected($activeCounty == ($c->slug ?? $c->name))>{{ $c->name }}</option>
        @endforeach
      </select>
      <select name="category" aria-label="Category">
        <option value="">All categories</option>
        @foreach($categories as $cat)
          <option value="{{ $cat->slug ?? $cat->id }}" @selected($activeCategory == ($cat->slug ?? $cat->id))>{{ $cat->name }}</option>
        @endforeach
      </select>
      <button class="btn" type="submit">Filter</button>
    </form>
    <span class="ex-count">{{ number_format($products->total()) }} listings</span>
  </div>

  <div class="ex-section">
    <div class="ex-grid">
      @forelse($products as $p)
        @php
          $resolvedMedia = app(\App\Services\ProductMediaResolver::class)->resolve($p);
          $img = $resolvedMedia['url'];
          $county = $p->county->name ?? null;
                @endphp
        <x-experience.card
          :href="route('marketplace.show',$p->slug)"
          :media="$img"
          :media-note="$resolvedMedia['label']"
          :poster="$img"
          :tag="$p->is_featured ? 'Featured' : ($p->is_spotlight_product ? 'Spotlight' : null)"
          :tag-tone="$p->is_spotlight_product ? 'red' : null"
          :meta="collect([$county, $p->category->name ?? null, $p->unit])->filter()->implode(' · ')"
          :title="$p->name"
          :copy="\Illuminate\Support\Str::limit(strip_tags($p->short_description ?? $p->description ?? ''),130)"
          action="View listing"
          :initials="strtoupper(substr($p->name,0,2))"
          :admin="auth()->user()?->isAdmin() ? 'Add, replace or delete product image' : null"
          :admin-href="auth()->user()?->isAdmin() ? route('experience.images.index', ['owner_type'=>'product', 'owner_id'=>$p->id]) : null"
        />
      @empty
        <div class="ex-empty"><strong>No listings match</strong>Try another county or clear the filters.</div>
      @endforelse
    </div>
    <div class="ex-pager">{{ $products->withQueryString()->links() }}</div>
  </div>
</div></section>
@endsection
