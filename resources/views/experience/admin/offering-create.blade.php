@extends('layouts.nexora')
@section('title',$institution->name.' · Add offering')
@section('content')
<section class="as-card"><h1>Add product, service or experience</h1><p>{{ $institution->name }} · {{ $institution->county?->name }}</p><a href="{{ route('institution.products.index',$institution->slug) }}">Back to offerings</a></section>
@if($errors->any())<div class="as-card" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<section class="as-card"><form method="POST" action="{{ route('institution.offerings.store',$institution->slug) }}">@csrf@include('experience.admin.offering-fields')<button type="submit" class="as-cta">Create offering</button></form></section>
@endsection