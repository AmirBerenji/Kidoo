<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'reviewable_id',
        'reviewable_type',
        'user_id',
        'rating',
        'comment'
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    // Polymorphic relationship
    public function reviewable()
    {
        return $this->morphTo();
    }

    // User who created the review
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
