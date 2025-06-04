<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function rework()
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

    // Register media collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('champions')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->singleFile();
    }

    // Register media conversions
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10);

        $this->addMediaConversion('large')
            ->width(800)
            ->height(600)
            ->nonQueued();
    }
}