<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'points',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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

    // ✅ ADDED: Unlock system relationships and methods
    public function unlocks(): HasMany
    {
        return $this->hasMany(UserUnlock::class);
    }

    // Helper method να unlock έναν champion
    public function unlockChampion($championId): bool
    {
        $champion = Champion::find($championId);
        
        if (!$champion) {
            return false;
        }

        // Έλεγξε αν ήδη έχει unlock
        if ($this->hasUnlocked($champion)) {
            return false; // Ήδη unlocked
        }

        // Έλεγξε αν έχει αρκετά points
        if ($this->points < $champion->unlock_cost) {
            return false; // Όχι αρκετά points
        }

        // Create the unlock record
        UserUnlock::create([
            'user_id' => $this->id,
            'unlockable_type' => Champion::class,
            'unlockable_id' => $championId,
            'cost_paid' => $champion->unlock_cost
        ]);

        // Αφαίρεσε τα points
        $this->decrement('points', $champion->unlock_cost);

        return true;
    }

    // Helper method να ελέγχουμε αν έχει unlock κάτι
    public function hasUnlocked($model): bool
    {
        return $this->unlocks()
                   ->where('unlockable_type', get_class($model))
                   ->where('unlockable_id', $model->id)
                   ->exists();
    }

    // Get unlocked champion IDs
    public function getUnlockedChampionIds(): array
    {
        return $this->unlocks()
                   ->where('unlockable_type', Champion::class)
                   ->pluck('unlockable_id')
                   ->toArray();
    }

    // Check if user has unlocked a specific champion
    public function hasUnlockedChampion($championId): bool
    {
        return $this->unlocks()
                   ->where('unlockable_type', Champion::class)
                   ->where('unlockable_id', $championId)
                   ->exists();
    }
}