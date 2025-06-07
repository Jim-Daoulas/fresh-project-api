<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserUnlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'unlockable_type',
        'unlockable_id',
        'cost_paid',
    ];

    protected $casts = [
        'cost_paid' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unlockable(): MorphTo
    {
        return $this->morphTo();
    }

    // Scope για champions
    public function scopeChampions($query)
    {
        return $query->where('unlockable_type', Champion::class);
    }

    // Scope για skins
    public function scopeSkins($query)
    {
        return $query->where('unlockable_type', Skin::class);
    }

    // Helper method για να ελέγξουμε αν κάτι είναι unlocked
    public static function isUnlocked(int $userId, string $type, int $id): bool
    {
        return static::where('user_id', $userId)
            ->where('unlockable_type', $type)
            ->where('unlockable_id', $id)
            ->exists();
    }

    // Helper method για unlock
    public static function unlock(int $userId, Model $unlockable): bool
    {
        try {
            static::create([
                'user_id' => $userId,
                'unlockable_type' => get_class($unlockable),
                'unlockable_id' => $unlockable->id,
                'cost_paid' => $unlockable->unlock_cost,
            ]);
            return true;
        } catch (\Exception $e) {
            return false; // Ήδη unlocked ή άλλο σφάλμα
        }
    }
}