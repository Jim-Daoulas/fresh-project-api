<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    ];

    // Append the media URL to the JSON response
    protected $appends = ['image_url'];

    public function champion(): BelongsTo
    {
        return $this->belongsTo(Champion::class);
    }

    // Override the image_url attribute to return media URL
    public function getImageUrlAttribute()
    {
        // Get the media URL from the 'skins' collection
        $mediaUrl = $this->getFirstMediaUrl('skins');
        
        // Return media URL if exists, otherwise return the original value
        return $mediaUrl ?: $this->attributes['image_url'] ?? null;
    }
}