<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPoints extends Model
{
    protected $fillable = [
        'user_id',
        'total_points',
        'last_daily_login'
    ];

    protected $casts = [
        'last_daily_login' => 'date'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}