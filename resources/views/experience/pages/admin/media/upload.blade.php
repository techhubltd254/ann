@extends('layouts.nexora')
@section('title','Media uploads · Admin')
@section('content')
<section class="as-card"><h1>Choose the responsible owner before uploading</h1><p>For a product video: Institutions → choose institution → Products → Edit product → Upload & publish.</p><p><a class="as-cta" href="{{ route('admin.portal') }}">Choose institution</a> <a class="as-cta" href="{{ route('admin.uploads') }}">Entity video upload · up to 2 GiB</a> <a class="as-cta" href="{{ route('media.library') }}">Back to Media Library</a></p></section>
<section class="as-card"><h2>Images and 3D source files</h2><p>Up to 50 MiB per file, 10 files per batch. Large videos use the verified chunked uploader, not this form.</p><form method="POST" action="{{ route('media.store') }}" enctype="multipart/form-data">@csrf<input type="file" name="files[]" multiple required accept="image/jpeg,image/png,image/webp,.glb,.gltf"><button type="submit" class="as-cta">Upload source files</button></form>@if($errors->any())@foreach($errors->all() as $e)<p role="alert">{{ $e }}</p>@endforeach@endif</section>
@endsection
