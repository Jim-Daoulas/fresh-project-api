<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

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
    ];

    protected $casts = [
        'stats' => 'array',
    ];

    // Append the media URL to the JSON response
    protected $appends = ['image_url'];

    public function abilities(): HasMany
    {
        return $this->hasMany(Ability::class);
    }

    public function skins(): HasMany
    {
        return $this->hasMany(Skin::class);
    }

    public function rework(): HasOne
    {
        return $this->hasOne(Rework::class);
    }

    // Override the image_url attribute to return media URL
    public function getImageUrlAttribute()
    {
        // Get the media URL from the 'champions' collection
        $mediaUrl = $this->getFirstMediaUrl('champions');
        
        // Return media URL if exists, otherwise return the original value
        return $mediaUrl ?: $this->attributes['image_url'] ?? null;
    }

    // Scopes για εύκολη αναζήτηση
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Helper methods
    public function hasRework(): bool
    {
        return $this->rework !== null;
    }

    public function getAbilitiesCount(): int
    {
        return $this->abilities()->count();
    }

    public function getSkinsCount(): int
    {
        return $this->skins()->count();
    }

    public function getStatValue(string $stat): int
    {
        return $this->stats[$stat] ?? 0;
    }

    public function getRoleColorAttribute(): string
    {
        $colors = [
            'Assassin' => 'red',
            'Fighter' => 'orange',
            'Mage' => 'blue',
            'Marksman' => 'green',
            'Support' => 'cyan',
            'Tank' => 'gray',
        ];

        return $colors[$this->role] ?? 'gray';
    }
}