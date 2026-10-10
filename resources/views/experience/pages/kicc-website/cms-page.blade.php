@extends('layouts.app')
@section('title',$page->title)
@section('content')<article class="wrap" style="padding:40px;max-width:1000px"><h1>{{ $page->title }}</h1>@if($page->excerpt)<p>{{ $page->excerpt }}</p>@endif<div class="cms-content">{!! \App\Support\SafeCmsHtml::clean($page->content) !!}</div></article>@endsection
