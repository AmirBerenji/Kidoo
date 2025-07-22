<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NannyTranslation extends Model
{
    protected $fillable = [
        'language_code',
        'full_name',
        'specialization',
        'age_groups',
    ];
}
