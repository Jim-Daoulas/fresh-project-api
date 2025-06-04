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

    protected $appends = ['avatar_url'];

    // Βεβαιωθείτε ότι τα media φορτώνονται
    protected $with = ['media'];

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

    // Avatar URL που συνδυάζει Spatie Media και fallback
    public function getAvatarUrlAttribute()
    {
        // Προτίμησε το WebP conversion που είναι πιο συμβατό
        $thumbUrl = $this->getFirstMediaUrl('avatar', 'thumb');
        if ($thumbUrl) {
            return $thumbUrl;
        }

        // Δοκίμασε το original αρχείο
        $mediaUrl = $this->getFirstMediaUrl('avatar');
        if ($mediaUrl) {
            return $mediaUrl;
        }

        // Fallback στο image_url field
        if ($this->image_url) {
            if (str_starts_with($this->image_url, 'http')) {
                return $this->image_url;
            }
            return asset('storage/' . $this->image_url);
        }

        return null;
    }

    // Override για να σιγουρευτούμε ότι το media serialization δουλεύει
    public function toArray()
    {
        $array = parent::toArray();
        
        // Προσθήκη media information
        $array['has_media'] = $this->hasMedia('avatar');
        $array['media_url'] = $this->getFirstMediaUrl('avatar');
        $array['media_count'] = $this->getMedia('avatar')->count();
        
        return $array;
    }

    // Spatie Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->acceptsMimeTypes([
                'image/jpeg', 
                'image/png', 
                'image/webp', 
                'image/gif',
                'image/avif'  // Προσθήκη AVIF support
            ])
            ->singleFile();
    }

    // Media Conversions
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->performOnCollections('avatar');
    }
}