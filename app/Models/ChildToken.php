<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildToken extends Model
{
    /** @use HasFactory<\Database\Factories\ChildTokenFactory> */
    use HasFactory;

    protected $table = 'child_tokens';

    protected $fillable = [
        'uuid',
        'isused',
        'useddate',
    ];


}
