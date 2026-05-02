<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorTranslation extends Model
{
    use HasFactory;

    protected $table = 'doctor_translations';

    protected $fillable = [
        'doctor_id',
        'language_id',
        'name',
        'bio',
        'education',
        'address',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    // Relationship with Doctor
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    // Relationship with Language
    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
