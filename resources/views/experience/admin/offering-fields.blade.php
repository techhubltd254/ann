<div class="up-grid">
<label>Name<input name="name" value="{{ old('name',$product->name) }}" required></label>
<label>Type<select name="offering_kind">@foreach(['product','service','experience'] as $v)<option value="{{ $v }}" @selected(old('offering_kind',$product->offering_kind)===$v)>{{ ucfirst($v) }}</option>@endforeach</select></label>
<label>Publication<select name="publication_status">@foreach(['draft','active'] as $v)<option value="{{ $v }}" @selected(old('publication_status',$product->status)===$v)>{{ $v==='active'?'Publish to public site':'Draft · private' }}</option>@endforeach</select></label>
<label>Pricing<select name="price_mode">@foreach(['fixed'=>'Published fixed price','from'=>'Published starting price','enquiry'=>'Price on enquiry'] as $v=>$label)<option value="{{ $v }}" @selected(old('price_mode',$product->price_mode)===$v)>{{ $label }}</option>@endforeach</select></label>
<label>Price (KES; blank for enquiry)<input name="price" type="number" min="0" step="0.01" value="{{ old('price',$product->price_mode==='enquiry'?null:$product->price) }}"></label>
<label>Stock / available slots<input name="stock" type="number" min="0" value="{{ old('stock',$product->variants->first()?->stock??0) }}" required></label>
<label>Unit<input name="unit" value="{{ old('unit',$product->unit) }}" maxlength="50"></label>
<label>Description<textarea name="description" rows="5">{{ old('description',$product->description) }}</textarea></label>
<label>Official booking / enquiry URL<input name="booking_url" type="url" value="{{ old('booking_url',$product->booking_url) }}"></label>
<label>Source page URL<input name="source_url" type="url" value="{{ old('source_url',$product->source_url) }}"></label>
<label>Duration in minutes (experiences)<input name="duration_minutes" type="number" min="1" value="{{ old('duration_minutes',$product->offering_details['duration_minutes']??null) }}"></label>
<label>Maximum guests (if verified)<input name="max_guests" type="number" min="1" value="{{ old('max_guests',$product->offering_details['max_guests']??null) }}"></label>
<label>Inclusions (one per line)<textarea name="inclusions" rows="5">{{ old('inclusions',implode("\n",$product->offering_details['inclusions']??[])) }}</textarea></label>
</div>