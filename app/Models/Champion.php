<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Champion extends Model
{
    use HasFactory;

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
