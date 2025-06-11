<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
    protected $appends = ['image_url'];

    public function champion(): BelongsTo
    {
        return $this->belongsTo(Champion::class);
    }

    public function unlockedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'skin_unlocks')
                    ->withTimestamps()
                    ->withPivot('unlocked_at');
    }

    // Override the image_url attribute to return media URL
    public function getImageUrlAttribute()
    {
        // Get the media URL from the 'skins' collection
        $mediaUrl = $this->getFirstMediaUrl('skins');
        
        // Return media URL if exists, otherwise return the original value
        return $mediaUrl ?: $this->attributes['image_url'] ?? null;
    }

    // ✅ MAIN METHOD - Έλεγχος αν είναι unlocked για user (same as Champion)
    public function isUnlockedForUser($userId = null): bool
    {
        // Αν δεν έχουμε user ID (guest), μόνο default unlocked
        if (!$userId) {
            return $this->is_unlocked_by_default;
        }

        // Αν είναι default unlocked, return true
        if ($this->is_unlocked_by_default) {
            return true;
        }

        // Έλεγξε αν ο user έχει unlock αυτό το skin
        return $this->unlockedByUsers()->where('user_id', $userId)->exists();
    }

    // Helper methods (same pattern as Champion)
    public function isUnlockedByDefault(): bool
    {
        return $this->is_unlocked_by_default;
    }

    public function isUnlockedBy(User $user): bool
    {
        return $this->isUnlockedForUser($user->id);
    }

    public function canBeUnlockedBy(User $user): bool
    {
        return !$this->is_unlocked_by_default && 
               !$this->isUnlockedForUser($user->id) && 
               $user->points >= $this->unlock_cost &&
               $this->champion->isUnlockedForUser($user->id); // Extra check για champion
    }

    // Scopes (same pattern as Champion)
    public function scopeUnlockedForUser($query, $userId = null)
    {
        if (!$userId) {
            return $query->where('is_unlocked_by_default', true);
        }

        return $query->where(function($q) use ($userId) {
            $q->where('is_unlocked_by_default', true)
              ->orWhereHas('unlockedByUsers', function($subQuery) use ($userId) {
                  $subQuery->where('user_id', $userId);
              });
        });
    }

    public function scopeLockedForUser($query, $userId = null)
    {
        if (!$userId) {
            return $query->where('is_unlocked_by_default', false);
        }

        $user = User::find($userId);
        if (!$user) {
            return $query->where('is_unlocked_by_default', false);
        }

        $unlockedIds = $user->getUnlockedSkinIds();
        
        return $query->where('is_unlocked_by_default', false)
                    ->whereNotIn('id', $unlockedIds);
    }

    public function scopeAvailableForUser($query, $userId = null)
    {
        // Skins που ο user μπορεί να δει (από unlocked champions)
        if (!$userId) {
            return $query->whereHas('champion', function($q) {
                $q->where('is_unlocked_by_default', true);
            });
        }

        $user = User::find($userId);
        if (!$user) {
            return $query->whereHas('champion', function($q) {
                $q->where('is_unlocked_by_default', true);
            });
        }

        $unlockedChampionIds = array_merge(
            Champion::where('is_unlocked_by_default', true)->pluck('id')->toArray(),
            $user->getUnlockedChampionIds()
        );
        
        return $query->whereIn('champion_id', $unlockedChampionIds);
    }
}