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

    public function getImageUrlAttribute()
{
    $mediaUrl = $this->getFirstMediaUrl('avatars');
    
    if ($mediaUrl) {
        // Αν το URL δεν έχει domain, πρόσθεσε το app URL
        if (!str_starts_with($mediaUrl, 'http')) {
            return config('app.url') . $mediaUrl;
        }
        return $mediaUrl;
    }
    
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
}