<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Skin extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'champion_id',
        'image_url',
        'description',
        'unlock_cost',
        'is_unlocked_by_default', // Αν και δεν το χρησιμοποιούμε
    ];

    protected $casts = [
        'unlock_cost' => 'integer',
        'is_unlocked_by_default' => 'boolean',
    ];

    // Media Library Configuration (ίδια με Champion)
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatars')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10);
    }

    // Accessor για image_url από media
    public function getImageUrlAttribute(): ?string
    {
        if ($this->hasMedia('avatars')) {
            return $this->getFirstMediaUrl('avatars');
        }
        
        return $this->attributes['image_url'] ?? null;
    }

    // Relationships
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

    // ✅ MAIN METHOD - Έλεγχος αν είναι unlocked για user (ίδια λογική με Champion)
    public function isUnlockedForUser($userId = null): bool
    {
        \Log::info("=== DEBUG Skin isUnlockedForUser ===");
        \Log::info("Skin ID: {$this->id}, Name: {$this->name}");
        \Log::info("Champion ID: {$this->champion_id}");
        \Log::info("User ID: " . ($userId ?? 'null'));

        // Βρες το πρώτο skin αυτού του champion (default unlocked)
        $firstSkin = Skin::where('champion_id', $this->champion_id)
            ->orderBy('id', 'asc')
            ->first();

        $isFirstSkin = ($this->id === $firstSkin->id);
        \Log::info("Is first skin: " . ($isFirstSkin ? 'true' : 'false'));

        // Αν δεν έχουμε user ID (guest), μόνο το πρώτο skin είναι unlocked
        if (!$userId) {
            \Log::info("No user ID - returning first skin status: " . ($isFirstSkin ? 'true' : 'false'));
            return $isFirstSkin;
        }

        // Αν είναι το πρώτο skin, είναι πάντα unlocked
        if ($isFirstSkin) {
            \Log::info("Skin is first skin - returning true");
            return true;
        }

        // Έλεγξε αν ο user έχει unlock αυτό το skin
        $isUnlocked = $this->unlockedByUsers()->where('user_id', $userId)->exists();
        \Log::info("DB check result: " . ($isUnlocked ? 'UNLOCKED' : 'LOCKED'));
        
        return $isUnlocked;
    }

    // Accessor για το frontend
    public function getIsLockedAttribute(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        return !$this->isUnlockedForUser($user?->id);
    }

    // Helper methods
    public function isFirstSkin(): bool
    {
        $firstSkin = Skin::where('champion_id', $this->champion_id)
            ->orderBy('id', 'asc')
            ->first();
        
        return $this->id === $firstSkin->id;
    }

    public function isUnlockedBy(User $user): bool
    {
        return $this->isUnlockedForUser($user->id);
    }

    public function canBeUnlockedBy(User $user): bool
    {
        return !$this->isFirstSkin() && 
               !$this->isUnlockedForUser($user->id) && 
               $user->points >= $this->unlock_cost &&
               $this->champion->isUnlockedForUser($user->id); // Πρέπει να έχει unlock το champion πρώτα
    }

    // Scopes
    public function scopeForChampion($query, $championId)
    {
        return $query->where('champion_id', $championId);
    }

    public function scopeUnlockedForUser($query, $userId = null)
    {
        if (!$userId) {
            // For guests: only first skin of each champion
            return $query->whereRaw('id = (SELECT MIN(id) FROM skins s2 WHERE s2.champion_id = skins.champion_id)');
        }

        return $query->where(function($q) use ($userId) {
            // First skin OR explicitly unlocked
            $q->whereRaw('id = (SELECT MIN(id) FROM skins s2 WHERE s2.champion_id = skins.champion_id)')
              ->orWhereHas('unlockedByUsers', function($subQuery) use ($userId) {
                  $subQuery->where('user_id', $userId);
              });
        });
    }

    public function scopeNotFirstSkin($query)
    {
        return $query->whereRaw('id != (SELECT MIN(id) FROM skins s2 WHERE s2.champion_id = skins.champion_id)');
    }

    public function scopeFirstSkin($query)
    {
        return $query->whereRaw('id = (SELECT MIN(id) FROM skins s2 WHERE s2.champion_id = skins.champion_id)');
    }

    // Accessor για unlock cost με default value
    public function getUnlockCostAttribute($value)
    {
        return $value ?? 10; // Default unlock cost για skins
    }
}