<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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

    // ✅ ADDED: Media Library Configuration
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

    // ✅ ADDED: Accessor για image_url από media
    public function getImageUrlAttribute(): ?string
    {
        // Αν έχει media, επέστρεψε το URL
        if ($this->hasMedia('avatars')) {
            return $this->getFirstMediaUrl('avatars');
        }
        
        // Αλλιώς επέστρεψε το image_url field
        return $this->attributes['image_url'] ?? null;
    }

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