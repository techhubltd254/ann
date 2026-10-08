@extends('layouts.app')
@section('title','Source components — KICC')
@section('content')
<x-experience.head eyebrow="Mother / KICC · granular editing" title="Source-level content control." lead="Edit registered text, rich text, images, links, visibility and repeaters through the existing TiDB component API. Optimistic revision checks prevent one editor overwriting another." />
<section class="wrap admin-module" data-component-editor>
 <div class="admin-hub-toolbar"><label>Find a registered component<input type="search" data-component-search placeholder="Label, source file or component ID"></label><button class="btn" data-component-load>Load / refresh</button><span data-component-status role="status">Components load on request.</span></div>
 <div data-component-results></div><button class="btn ghost" data-component-more hidden>Show next 30</button>
</section>
@endsection
