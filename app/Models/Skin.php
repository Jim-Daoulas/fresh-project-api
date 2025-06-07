<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Skin extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'champion_id',
        'name',
        'image_url',
        'description',
        'unlock_cost',
        'is_unlocked_by_default',
    ];

    protected $casts = [
        'unlock_cost' => 'integer',
        'is_unlocked_by_default' => 'boolean',
    ];

    // Append the media URL to the JSON response
    protected $appends = ['image_url', 'is_unlocked_by_default'];

    public function champion(): BelongsTo
    {
        return $this->belongsTo(Champion::class);
    }

    public function unlocks(): MorphMany
    {
        return $this->morphMany(UserUnlock::class, 'unlockable');
    }

    // Override the image_url attribute to return media URL
    public function getImageUrlAttribute()
    {
        // Get the media URL from the 'skins' collection
        $mediaUrl = $this->getFirstMediaUrl('skins');
        
        // Return media URL if exists, otherwise return the original value
        return $mediaUrl ?: $this->attributes['image_url'] ?? null;
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

    // Scope για unlocked skins
    public function scopeUnlockedByUser($query, User $user)
    {
        $unlockedIds = $user->getUnlockedSkinIds();
        
        return $query->where(function($q) use ($unlockedIds) {
            $q->where('is_unlocked_by_default', true)
              ->orWhereIn('id', $unlockedIds);
        });
    }

    // Scope για locked skins
    public function scopeLockedForUser($query, User $user)
    {
        $unlockedIds = $user->getUnlockedSkinIds();
        
        return $query->where('is_unlocked_by_default', false)
                    ->whereNotIn('id', $unlockedIds);
    }

    // Accessor για API
    public function getIsUnlockedByDefaultAttribute(): bool
    {
        return $this->attributes['is_unlocked_by_default'] ?? false;
    }
}