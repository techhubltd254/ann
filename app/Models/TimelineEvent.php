<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TimelineEvent extends Model {
    protected $fillable = ['year','title','description','sort_order'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}