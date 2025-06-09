<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserUnlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'unlockable_type',
        'unlockable_id', 
        'cost_paid',
    ];

    protected $casts = [
        'cost_paid' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unlockable(): MorphTo
    {
        return $this->morphTo();
    }

    // Helper scopes
    public function scopeChampions($query)
    {
        return $query->where('unlockable_type', Champion::class);
    }

    public function scopeSkins($query)
    {
        return $query->where('unlockable_type', Skin::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}