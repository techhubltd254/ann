@extends('layouts.nexora')
@section('content')
<section class="as-page"><h1>Private revenue allocation</h1><p>Mother admin only. Internal allocation is never included in public pages, buyer receipts or non-mother API responses.</p>
<p>This KES 100,000 example illustrates the existing allocation; it is not a report of received revenue.</p>
@include('components.pipeline-fee-breakdown', ['subtotal'=>100000,'pipelineCode'=>'A1'])
<p><a href="{{ url('/admin/kicc/revenue-pipelines') }}">Open private pipeline registry</a> · <a href="{{ url('/admin/kicc?tab=earnings') }}">Open earnings</a></p></section>
@endsection
