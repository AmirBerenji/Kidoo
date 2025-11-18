<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'email',
        'phone',
        'specialization',
        'experience_years',
        'license_number',
        'image',
        'location_id',
        'status',
    ];

    protected $casts = [
        'experience_years' => 'integer',
    ];

    // Relationship with translations
    public function translations()
    {
        return $this->hasMany(DoctorTranslation::class);
    }

    // Relationship with location
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    // Relationship with user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Get translation for specific language
    public function translation($languageCode = null)
    {
        if (!$languageCode) {
            $languageCode = app()->getLocale();
        }

        return $this->translations()
            ->whereHas('language', function($query) use ($languageCode) {
                $query->where('code', $languageCode);
            })
            ->first();
    }

    // Get translated attribute
    public function translate($languageCode = null)
    {
        return $this->translation($languageCode);
    }

    // Accessor for name
    public function getNameAttribute()
    {
        return $this->translation()?->name ?? '';
    }

    // Accessor for bio
    public function getBioAttribute()
    {
        return $this->translation()?->bio ?? '';
    }

    // Accessor for education
    public function getEducationAttribute()
    {
        return $this->translation()?->education ?? '';
    }

    // Accessor for address
    public function getAddressAttribute()
    {
        return $this->translation()?->address ?? '';
    }

    // Scope for active doctors
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
