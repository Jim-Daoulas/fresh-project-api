<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'points',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'points' => 'integer',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    // ✅ CHAMPION UNLOCK SYSTEM
    public function unlockedChampions(): BelongsToMany
    {
        return $this->belongsToMany(Champion::class, 'champion_unlocks')
                    ->withTimestamps()
                    ->withPivot('unlocked_at');
    }

    // Unlock έναν champion
    public function unlockChampion($championId): bool
    {
        $champion = Champion::find($championId);
        
        if (!$champion) {
            return false;
        }

        // Έλεγξε αν ήδη έχει unlock
        if ($this->hasUnlockedChampion($championId)) {
            return false;
        }

        // Έλεγξε αν είναι default unlocked
        if ($champion->is_unlocked_by_default) {
            return false; // Δεν χρειάζεται unlock
        }

        // Έλεγξε αν έχει αρκετά points
        if ($this->points < $champion->unlock_cost) {
            return false;
        }

        // Create the unlock record
        $this->unlockedChampions()->attach($championId, [
            'unlocked_at' => now()
        ]);

        // Αφαίρεσε τα points
        $this->decrement('points', $champion->unlock_cost);

        return true;
    }

    // Έλεγξε αν έχει unlock έναν champion
    public function hasUnlockedChampion($championId): bool
    {
        return $this->unlockedChampions()->where('champion_id', $championId)->exists();
    }

    // Get unlocked champion IDs
    public function getUnlockedChampionIds(): array
    {
        return $this->unlockedChampions()->pluck('champion_id')->toArray();
    }
}