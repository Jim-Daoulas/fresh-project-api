<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Rework extends Model
{
    use HasFactory;

    protected $fillable = [
        'champion_id',
        'user_id',
        'updated_by',
        'title',
        'summary',
        'stats',
    ];

    protected $casts = [
        'stats' => 'array',
    ];

    // Boot method για validation και auto-tracking
    protected static function boot()
    {
        parent::boot();

        // Πριν τη δημιουργία, έλεγξε αν υπάρχει ήδη rework για αυτόν τον champion
        static::creating(function ($rework) {
            if (static::where('champion_id', $rework->champion_id)->exists()) {
                throw ValidationException::withMessages([
                    'champion_id' => 'This champion already has a rework proposal. Only one rework per champion is allowed.'
                ]);
            }
            
            // Όταν δημιουργείται, το updated_by είναι το ίδιο με το user_id
            $rework->updated_by = $rework->user_id;
        });

        // Κατά την ενημέρωση, έλεγξε μόνο αν αλλάζει το champion_id
        static::updating(function ($rework) {
            if ($rework->isDirty('champion_id')) {
                if (static::where('champion_id', $rework->champion_id)
                    ->where('id', '!=', $rework->id)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'champion_id' => 'This champion already has a rework proposal. Only one rework per champion is allowed.'
                    ]);
                }
            }
            
            // Auto-track ποιος έκανε την τελευταία ενημέρωση
            if (auth()->check()) {
                $rework->updated_by = auth()->id();
            }
        });
    }

    public function champion(): BelongsTo
    {
        return $this->belongsTo(Champion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function abilities(): HasMany
    {
        return $this->hasMany(ReworkAbility::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // Scopes
    public function scopeByChampion($query, $championId)
    {
        return $query->where('champion_id', $championId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('updated_at', 'desc');
    }

    // Accessors
    public function getAbilitiesCountAttribute(): int
    {
        return $this->abilities()->count();
    }

    public function getCommentsCountAttribute(): int
    {
        return $this->comments()->count();
    }

    public function getWasModifiedAttribute(): bool
    {
        return $this->created_at != $this->updated_at;
    }

    public function getModifiedByDifferentUserAttribute(): bool
    {
        return $this->user_id != $this->updated_by;
    }

    public function getFormattedStatsAttribute(): string
    {
        if (!$this->stats) {
            return 'No stats defined';
        }

        $formatted = [];
        foreach ($this->stats as $key => $value) {
            $formatted[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
        }

        return implode(', ', $formatted);
    }

    // Method για σύγκριση stats
    public function compareStatsWithChampion(): array
    {
        $championStats = $this->champion->stats ?? [];
        $reworkStats = $this->stats ?? [];
        $comparison = [];

        foreach ($reworkStats as $key => $value) {
            $originalValue = $championStats[$key] ?? 0;
            $comparison[$key] = [
                'original' => $originalValue,
                'rework' => $value,
                'difference' => $value - $originalValue,
                'percentage_change' => $originalValue > 0 ? (($value - $originalValue) / $originalValue) * 100 : 0
            ];
        }

        return $comparison;
    }
}