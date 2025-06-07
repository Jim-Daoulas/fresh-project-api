<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function unlocks(): HasMany
    {
        return $this->hasMany(UserUnlock::class);
    }

    // Progression System Methods
    public function addPoints(int $amount): void
    {
        $this->increment('points', $amount);
    }

    public function deductPoints(int $amount): bool
    {
        if ($this->points >= $amount) {
            $this->decrement('points', $amount);
            return true;
        }
        return false;
    }

    public function hasUnlocked(Model $unlockable): bool
    {
        return UserUnlock::isUnlocked(
            $this->id, 
            get_class($unlockable), 
            $unlockable->id
        );
    }

    public function canUnlock(Model $unlockable): bool
    {
        // Έλεγχος αν έχει αρκετά points και δεν το έχει ήδη unlock
        return $this->points >= $unlockable->unlock_cost && 
               !$this->hasUnlocked($unlockable) &&
               !$unlockable->is_unlocked_by_default;
    }

    public function unlock(Model $unlockable): array
    {
        // Έλεγχος αν είναι ήδη unlocked by default
        if ($unlockable->is_unlocked_by_default) {
            return [
                'success' => false,
                'message' => 'This item is already unlocked by default'
            ];
        }

        // Έλεγχος αν το έχει ήδη unlock
        if ($this->hasUnlocked($unlockable)) {
            return [
                'success' => false,
                'message' => 'You have already unlocked this item'
            ];
        }

        // Έλεγχος αν έχει αρκετά points
        if ($this->points < $unlockable->unlock_cost) {
            return [
                'success' => false,
                'message' => 'Insufficient points',
                'required' => $unlockable->unlock_cost,
                'current' => $this->points
            ];
        }

        // Προσπάθεια unlock
        if ($this->deductPoints($unlockable->unlock_cost)) {
            if (UserUnlock::unlock($this->id, $unlockable)) {
                return [
                    'success' => true,
                    'message' => 'Successfully unlocked!',
                    'remaining_points' => $this->points
                ];
            } else {
                // Αν αποτύχει, επιστροφή των points
                $this->addPoints($unlockable->unlock_cost);
                return [
                    'success' => false,
                    'message' => 'Failed to unlock. Please try again.'
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'An error occurred during unlock'
        ];
    }

    // Helper methods για να πάρουμε unlocked items
    public function getUnlockedChampions()
    {
        return $this->unlocks()
            ->where('unlockable_type', Champion::class)
            ->with('unlockable')
            ->get()
            ->pluck('unlockable');
    }

    public function getUnlockedSkins()
    {
        return $this->unlocks()
            ->where('unlockable_type', Skin::class)
            ->with('unlockable')
            ->get()
            ->pluck('unlockable');
    }

    public function getUnlockedChampionIds(): array
    {
        return $this->unlocks()
            ->where('unlockable_type', Champion::class)
            ->pluck('unlockable_id')
            ->toArray();
    }

    public function getUnlockedSkinIds(): array
    {
        return $this->unlocks()
            ->where('unlockable_type', Skin::class)
            ->pluck('unlockable_id')
            ->toArray();
    }
}