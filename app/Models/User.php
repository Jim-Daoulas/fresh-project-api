<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'points',
        'last_login_date',
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
            'last_login_date' => 'date',
        ];
    }

    // ✅ DAILY LOGIN SYSTEM
    public function checkDailyLogin(): bool
    {
        $today = Carbon::today();
        
        // Αν δεν έχει κάνει login σήμερα
        if (!$this->last_login_date || !$this->last_login_date->isSameDay($today)) {
            $this->increment('points', 5);
            $this->update(['last_login_date' => $today]);
            return true; // Πήρε daily bonus
        }
        
        return false; // Δεν πήρε bonus
    }

    // ✅ COMMENT POINTS SYSTEM
    public function addCommentPoints(): void
    {
        $this->increment('points', 10);
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

    public function unlockChampion($championId): bool
    {
        $champion = Champion::find($championId);
        
        if (!$champion) {
            return false;
        }

        if ($this->hasUnlockedChampion($championId)) {
            return false;
        }

        if ($champion->is_unlocked_by_default) {
            return false;
        }

        if ($this->points < $champion->unlock_cost) {
            return false;
        }

        $this->unlockedChampions()->attach($championId, [
            'unlocked_at' => now()
        ]);

        $this->decrement('points', $champion->unlock_cost);

        return true;
    }

    public function hasUnlockedChampion($championId): bool
    {
        return $this->unlockedChampions()->where('champion_id', $championId)->exists();
    }

    public function getUnlockedChampionIds(): array
    {
        return $this->unlockedChampions()->pluck('champion_id')->toArray();
    }
}