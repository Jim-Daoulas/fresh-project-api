<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Ability extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'champion_id',
        'name',
        'key',
        'description',
        'image_url',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
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
        // Get the media URL from the 'abilities' collection
        $mediaUrl = $this->getFirstMediaUrl('abilities');
        
        // Return media URL if exists, otherwise return the original value
        return $mediaUrl ?: $this->attributes['image_url'] ?? null;
    }
}
