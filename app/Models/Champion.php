<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Champion extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

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

    // Media Library Configuration
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

    public function unlockedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'champion_unlocks')
            ->withTimestamps()
            ->withPivot('unlocked_at');
    }

    // ✅ MAIN METHOD - Έλεγχος αν είναι unlocked για user
    public function isUnlockedForUser($userId = null): bool
    {
        \Log::info("=== DEBUG isUnlockedForUser ===");
        \Log::info("Champion ID: {$this->id}, Name: {$this->name}");
        \Log::info("User ID: " . ($userId ?? 'null'));
        \Log::info("is_unlocked_by_default: " . ($this->is_unlocked_by_default ? 'true' : 'false'));

        // Αν δεν έχουμε user ID (guest), μόνο default unlocked
        if (!$userId) {
            \Log::info("No user ID - returning default unlock status: " . ($this->is_unlocked_by_default ? 'true' : 'false'));
            return $this->is_unlocked_by_default;
        }

        // Αν είναι default unlocked, return true
        if ($this->is_unlocked_by_default) {
            \Log::info("Champion is unlocked by default - returning true");
            return true;
        }

        // Έλεγξε αν ο user έχει unlock αυτόν τον champion
        $isUnlocked = $this->unlockedByUsers()->where('user_id', $userId)->exists();
        \Log::info("DB check result: " . ($isUnlocked ? 'UNLOCKED' : 'LOCKED'));
        
        // Extra debug - δες τι unlocks έχει ο user
        $userUnlocks = $this->unlockedByUsers()->where('user_id', $userId)->get();
        \Log::info("User unlocks for this champion: " . $userUnlocks->count());
        
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
               $user->points >= $this->unlock_cost;
    }

    // Scopes
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
}