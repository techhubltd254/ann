<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Article extends Model { protected $fillable = ['title','slug','excerpt','content','category','author','featured_image','tags','status','published_at'];
    protected function casts(): array { return ['tags'=>'json','published_at'=>'datetime']; }
    protected static function booted(): void { static::creating(fn($a)=>$a->slug??=Str::slug($a->title)); }
    public function scopePublished($q) { return $q->where('status','published')->whereNotNull('published_at'); }
}

class JobListing extends Model { protected $fillable = ['title','description','department','location','type','closing_date','is_active'];
    protected $table = 'job_listings';
    protected function casts(): array { return ['closing_date'=>'date','is_active'=>'boolean']; }
    public function applications() { return $this->hasMany(JobApplication::class); }
}

class JobApplication extends Model { protected $fillable = ['job_listing_id','name','email','phone','cover_letter','cv_path'];
    protected $table = 'job_applications';
    public function job() { return $this->belongsTo(JobListing::class); }
}

class NewsletterSubscriber extends Model { protected $fillable = ['email','is_active'];
    protected $table = 'newsletter_subscribers';
    protected function casts(): array { return ['is_active'=>'boolean']; }
}

class EventBooking extends Model { protected $fillable = ['reference','first_name','last_name','organization','email','phone','event_name','event_type','expected_attendees','event_date','duration_days','preferred_venue','needs_catering','catering_details','needs_av','av_requirements','additional_info','status'];
    protected $table = 'event_bookings';
    protected function casts(): array { return ['event_date'=>'date','needs_catering'=>'boolean','needs_av'=>'boolean']; }
    protected static function booted(): void { static::creating(fn($b)=>$b->reference??='KICC-EVT-'.strtoupper(Str::random(10))); }
}