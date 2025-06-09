<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function unlockedChampions(): BelongsToMany
    {
        return $this->belongsToMany(Champion::class, 'champion_unlocks')
                    ->withTimestamps()
                    ->withPivot('unlocked_at');
    }

    // Helper method να unlock έναν champion
    public function unlockChampion($championId): bool
    {
        // Έλεγξε αν ήδη έχει unlock
        if ($this->unlockedChampions()->where('champion_id', $championId)->exists()) {
            return false; // Ήδη unlocked
        }

        $this->unlockedChampions()->attach($championId, [
            'unlocked_at' => now()
        ]);

        return true;
    }

    // Helper method να ελέγχουμε αν έχει unlock έναν champion
    public function hasUnlockedChampion($championId): bool
    {
        return $this->unlockedChampions()->where('champion_id', $championId)->exists();
    }
}