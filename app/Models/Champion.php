<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Champion extends Model
{
    protected $fillable = [
        'name',
        'title', 
        'role',
        'region',
        'description',
        'image_url',
        'stats',
        'unlock_cost',
        'is_unlocked_by_default'
    ];

    protected $casts = [
        'stats' => 'array',
        'is_unlocked_by_default' => 'boolean'
    ];

    // Append is_locked to JSON output
    protected $appends = ['is_locked'];

    public function abilities(): HasMany
    {
        return $this->hasMany(Ability::class);
    }

    public function skins(): HasMany
    {
        return $this->hasMany(Skin::class);
    }

    public function unlocks(): MorphMany
    {
        return $this->morphMany(UserUnlock::class, 'unlockable');
    }

    // Check if champion is locked for current user
    public function getIsLockedAttribute(): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return !$this->is_unlocked_by_default;
        }

        // If unlocked by default, it's not locked
        if ($this->is_unlocked_by_default) {
            return false;
        }

        // Check if user has unlocked this champion
        return !$this->unlocks()->where('user_id', $user->id)->exists();
    }

    // Scope for champions locked for a specific user
    public function scopeLockedForUser($query, $user)
    {
        $userId = is_object($user) ? $user->id : $user;
        
        return $query->where(function ($q) use ($userId) {
            $q->where('is_unlocked_by_default', false)
              ->whereDoesntHave('unlocks', function ($unlockQuery) use ($userId) {
                  $unlockQuery->where('user_id', $userId);
              });
        });
    }

    // Scope for champions unlocked for a specific user
    public function scopeUnlockedForUser($query, $user)
    {
        $userId = is_object($user) ? $user->id : $user;
        
        return $query->where(function ($q) use ($userId) {
            $q->where('is_unlocked_by_default', true)
              ->orWhereHas('unlocks', function ($unlockQuery) use ($userId) {
                  $unlockQuery->where('user_id', $userId);
              });
        });
    }
}