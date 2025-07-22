<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = ['code', 'name'];

    public function nannies()
    {
        return $this->belongsToMany(Nanny::class, 'nanny_languages');
    }
}
