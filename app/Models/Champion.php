<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    
    protected $appends = ['image_url'];

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatars')->singleFile();
    }

    // Override the image_url attribute to return media URL
    public function getImageUrlAttribute()
    {
        // Get the media URL from the 'avatars' collection
        $mediaUrl = $this->getFirstMediaUrl('avatars');
        
        // Return media URL if exists, otherwise return the original value
        return $mediaUrl ?: $this->attributes['image_url'] ?? null;
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
}