@extends('layouts.experience-live')
@section('title','Page Atlas — KICC')
@section('content')
<x-experience.head eyebrow="The experience build · route inventory" title="The whole experience.<br>One platform." lead="The 94 entries in the supplied reference. Data, availability and permissions come from the live backend—not the reference's demonstration figures." />
<div class="wrap ex-section ex-page"><div class="rb-table-wrap"><table class="rb-table"><thead><tr><th>Entry</th><th>Page / tab</th><th>Reference route</th><th>Layout / interaction</th></tr></thead><tbody>
@foreach($pages as $page)<tr><td>{{ $page['id'] }}</td><td>{{ $page['label'] }}</td><td><code>{{ $page['prototype'] }}</code></td><td>{{ $page['layout'] }}<br>{{ $page['interaction'] }}</td></tr>@endforeach
</tbody></table></div></div>
@endsection
