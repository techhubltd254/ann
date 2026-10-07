<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class Enquiry extends Model {use HasUuids;protected $guarded=['id'];public function record(){return $this->belongsTo(Record::class);}}
