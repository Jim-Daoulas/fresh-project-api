<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Skin extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'champion_id',
        'name',
        'image_url',
        'description',
    ];

    // Append the media URL to the JSON response
    protected $appends = ['image_url'];
    public function registerMediaConversion(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->performOnCollections('skins');
    }
    public function champion(): BelongsTo
    {
        return $this->belongsTo(Champion::class);
    }

    public function getImageUrlAttribute()
    {
        // Get the media URL from the 'avatars' collection
        $mediaUrl = $this->getFirstMediaUrl('skins');
        
        // Return media URL if exists, otherwise return the original value
        return $mediaUrl ?: $this->attributes['image_url'] ?? null;
    }
}