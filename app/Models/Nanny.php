<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nanny extends Model
{
    protected $fillable = [
        'user_id',
        'gender',
        'location_id',
        'years_experience',
        'working_hours',
        'days_available',
        'commitment_type',
        'hourly_rate',
        'fixed_package_description',
        'contact_enabled',
        'booking_type',
        'availability_calendar',
        'is_verified',
        'video_intro_url',
        'resume_url',
        'age_groups'
    ];

    protected $casts = [
        'availability_calendar' => 'array',
        'contact_enabled' => 'boolean',
        'is_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location() {
        return $this->belongsTo(Location::class);
    }

    public function translations() {
        return $this->hasMany(NannyTranslation::class);
    }

    public function languages() {
        return $this->belongsToMany(Language::class, 'nanny_languages');
    }

    public function services() {
        return $this->belongsToMany(Service::class, 'nanny_services');
    }

    public function degrees() {
        return $this->belongsToMany(Degree::class, 'nanny_degrees');
    }

    public function photos() {
        return $this->hasMany(NannyPhoto::class);
    }   //
}
