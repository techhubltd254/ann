<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class IncidentReport extends Model
{
    protected $fillable = ['user_id', 'type', 'description', 'county_id', 'location'];
}