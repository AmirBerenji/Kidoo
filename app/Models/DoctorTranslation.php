<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'language_id',
        'name',
        'bio',
        'education',
        'address',
    ];

    // Relationship with doctor
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    // Relationship with language
    public function language()
    {
        return $this->belongsTo(Language::class);
    }
}
