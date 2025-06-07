<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Champion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'role',
        'region',
        'description',
        'image_url',
        'stats',
        'unlock_cost',
        'is_unlocked_by_default',
    ];

    protected $casts = [
        'stats' => 'array',
        'unlock_cost' => 'integer',
        'is_unlocked_by_default' => 'boolean',
    ];

    public function abilities(): HasMany
    {
        return $this->hasMany(Ability::class);
    }

    public function skins(): HasMany
    {
        return $this->hasMany(Skin::class);
    }

    public function rework()
    {
        return $this->hasOne(Rework::class);
    }

    public function unlocks(): MorphMany
    {
        return $this->morphMany(UserUnlock::class, 'unlockable');
    }

    // Helper methods για unlock system
    public function isUnlockedByDefault(): bool
    {
        return $this->is_unlocked_by_default;
    }

    public function isUnlockedBy(User $user): bool
    {
        return $this->is_unlocked_by_default || $user->hasUnlocked($this);
    }

    public function canBeUnlockedBy(User $user): bool
    {
        return !$this->is_unlocked_by_default && 
               !$user->hasUnlocked($this) && 
               $user->points >= $this->unlock_cost;
    }

    // Scope για unlocked champions
    public function scopeUnlockedByUser($query, User $user)
    {
        $unlockedIds = $user->getUnlockedChampionIds();
        
        return $query->where(function($q) use ($unlockedIds) {
            $q->where('is_unlocked_by_default', true)
              ->orWhereIn('id', $unlockedIds);
        });
    }

    // Scope για locked champions
    public function scopeLockedForUser($query, User $user)
    {
        $unlockedIds = $user->getUnlockedChampionIds();
        
        return $query->where('is_unlocked_by_default', false)
                    ->whereNotIn('id', $unlockedIds);
    }

    // Append attributes για API responses
    protected $appends = ['is_unlocked_by_default'];

    // Accessor για API
    public function getIsUnlockedByDefaultAttribute(): bool
    {
        return $this->attributes['is_unlocked_by_default'] ?? false;
    }
}