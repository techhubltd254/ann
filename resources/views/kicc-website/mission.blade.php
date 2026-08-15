@extends('layouts.app')
@section('title', 'Mission, Vision & Mandate')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10 prose prose-sm max-w-none">
    <h1>Mission, Vision & Mandate</h1>
    <div>{!! $page->content ?? '<p>Content coming soon.</p>' !!}</div>
</div>
@endsection