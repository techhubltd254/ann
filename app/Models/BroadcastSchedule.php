<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastSchedule extends Model
{
    protected $fillable = ['name', 'exhibition_id', 'screen_target_type', 'screen_target_id',
        'starts_at', 'ends_at', 'content_type', 'media_asset_id', 'live_stream_id',
        'sort_order', 'is_active', 'metadata'];

    protected function casts(): array {
        return ['starts_at'=>'datetime','ends_at'=>'datetime','is_active'=>'boolean','sort_order'=>'integer','metadata'=>'array'];
    }

    public function exhibition() { return $this->belongsTo(Exhibition::class); }
    public function mediaAsset() { return $this->belongsTo(MediaAsset::class); }
    public function liveStream() { return $this->belongsTo(LiveStream::class); }
    public function target() { return $this->morphTo('screen_target'); }
}