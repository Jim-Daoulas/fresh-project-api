<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ReworkAbility extends Model
{
    use HasFactory;

    protected $fillable = [
        'rework_id',
        'name',
        'key',
        'description',
        'image_url',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    protected $appends = ['image_url'];

    public function rework(): BelongsTo
    {
        return $this->belongsTo(Rework::class);
    }

    // Override the image_url attribute to return media URL
    public function getImageUrlAttribute()
    {
        // Get the media URL from the 'rework_abilities' collection
        $mediaUrl = $this->getFirstMediaUrl('rework_abilities');
        
        // Return media URL if exists, otherwise return the original value
        return $mediaUrl ?: $this->attributes['image_url'] ?? null;
    }
}
