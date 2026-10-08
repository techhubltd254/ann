@extends('layouts.admin-records')
@section('title', ($record->exists ? $record->name : 'Create record').' — KICC')
@section('admin-content')
<h2 class="ra-h2">{{ $record->exists ? 'Edit experience' : 'Create experience' }}</h2>
<p class="ra-muted" style="margin:12px 0 20px">{{ config('kicc.types')[$record->type] }} · changes are stored on the server, not in localStorage.</p>

<form method="POST" action="{{ $record->exists ? route('admin.update', $record) : route('admin.store') }}" class="ra-card">
  @csrf
  @if($record->exists)@method('PUT')@endif
  <input type="hidden" name="type" value="{{ $record->type }}">
  <input type="hidden" name="revision" value="{{ $record->revision ?? 1 }}">
  <div class="ra-fields">
    <label class="ra-field">Name<input name="name" required maxlength="240" value="{{ old('name', $record->name) }}"></label>
    <label class="ra-field">Public slug<input name="slug" required maxlength="180" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="{{ old('slug', $record->slug) }}"></label>
    <label class="ra-field">Publication
      <select name="status">
        <option value="draft" @selected(old('status', $record->status) === 'draft')>Draft · private</option>
        <option value="published" @selected(old('status', $record->status) === 'published')>Published · public</option>
      </select>
    </label>
    <label class="ra-field">Parent county / sector / institution
      <select name="parent_id">
        <option value="">None</option>
        @foreach($parents as $p)
          <option value="{{ $p->id }}" @selected(old('parent_id', $record->parent_id) === $p->id)>{{ $p->name }} · {{ $p->type }}</option>
        @endforeach
      </select>
    </label>
    <label class="ra-field ra-full">Description / page content
      <textarea name="description" maxlength="100000">{{ old('description', $record->description) }}</textarea>
    </label>
    <label class="ra-field ra-full">Structured original details (JSON)
      <textarea name="payload_json" rows="10" style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace">{{ old('payload_json', json_encode($record->payload ?? (object) [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) }}</textarea>
      <small class="ra-muted">Original fields are retained here. Price, capacity, source and relationships are validated; unsupported keys are rejected.</small>
    </label>
  </div>
  <button class="ra-btn" style="margin-top:8px">Save record</button>
</form>

@if($record->exists)
<section style="margin-top:44px">
  <h2 class="ra-h2">Product / service / room experience</h2>
  <div class="ra-notice">Upload owner photos or video. Publication of both the record and the media is required for public visibility. MP4/WebM/MOV accepted; MOV codec support varies. 360° video uses flat native playback here. AI analysis and 3D reconstruction are not performed.</div>
  <form method="POST" action="{{ route('admin.upload', $record) }}" enctype="multipart/form-data" class="ra-card">
    @csrf
    <div class="ra-fields">
      <label class="ra-field">Media title<input name="title" required maxlength="240"></label>
      <label class="ra-field">File<input type="file" name="file" required accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime,.mov"></label>
      <label class="ra-field">Format
        <select name="format"><option value="standard">Standard</option><option value="360">360° source video</option></select>
      </label>
      <label class="ra-field">Publication
        <select name="status"><option value="draft">Draft · private</option><option value="published">Published</option></select>
      </label>
      <label class="ra-field ra-full">Media description / alternative text<textarea name="description" maxlength="5000"></textarea></label>
    </div>
    <button class="ra-btn">Upload to server</button>
  </form>

  @if($record->media->isNotEmpty())
    <div class="ra-media">
      @foreach($record->media as $m)
        <article class="ra-media-card">
          @if($m->isVideo())
            <video src="{{ $m->url() }}" controls preload="metadata"></video>
          @else
            <img src="{{ $m->url() }}" alt="{{ $m->description ?: $m->title }}">
          @endif
          <div class="ra-media-body">
            <h3 class="ra-h3">{{ $m->title }}</h3>
            <p class="ra-status">{{ $m->status }} · {{ number_format($m->bytes / 1024 / 1024, 2) }} MB · {{ $m->disk }} · {{ $m->mime }}</p>
            <div class="ra-media-actions">
              <form method="POST" action="{{ route('admin.media.status', $m) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="{{ $m->status === 'published' ? 'draft' : 'published' }}">
                <button class="ra-btn ra-btn-ghost ra-btn-sm">{{ $m->status === 'published' ? 'Unpublish' : 'Publish' }} media</button>
              </form>
              <form method="POST" action="{{ route('admin.media.delete', $m) }}" onsubmit="return confirm('Permanently delete this file from storage and the media library?')">
                @csrf @method('DELETE')
                <button class="ra-btn ra-btn-danger ra-btn-sm">Delete media</button>
              </form>
            </div>
          </div>
        </article>
      @endforeach
    </div>
  @endif

  <form style="margin-top:40px" method="POST" action="{{ route('admin.delete', $record) }}" onsubmit="return confirm('Delete this record? Remove attached media first.')">
    @csrf @method('DELETE')
    <button class="ra-btn ra-btn-danger">Delete record</button>
  </form>
</section>
@endif
@endsection
