<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkinUnlock extends Model
{
    protected $fillable = [
        'user_id',
        'skin_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skin(): BelongsTo
    {
        return $this->belongsTo(Skin::class);
    }
}