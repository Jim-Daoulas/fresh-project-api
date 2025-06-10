<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        
        if (!$this->last_login_date || !$this->last_login_date->isSameDay($today)) {
            $this->increment('points', 5);
            $this->update(['last_login_date' => $today]);
            return true;
        }
        
        return false;
    }

    public function addCommentPoints(): void
    {
        $this->increment('points', 10);
    }

    public function addPoints(int $amount): void
    {
        $this->increment('points', $amount);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    // ✅ UNLOCK RELATIONSHIPS
    public function unlocks(): HasMany
    {
        return $this->hasMany(UserUnlock::class);
    }

    public function unlockedChampions(): BelongsToMany
    {
        return $this->belongsToMany(Champion::class, 'champion_unlocks')
            ->withTimestamps()
            ->withPivot('unlocked_at');
    }

    // ✅ GENERIC UNLOCK METHOD
    public function unlock($item): array
    {
        // Έλεγχος τι είδους item είναι
        if ($item instanceof Champion) {
            return $this->unlockChampion($item);
        } elseif ($item instanceof Skin) {
            return $this->unlockSkin($item);
        }

        return [
            'success' => false,
            'message' => 'Invalid item type'
        ];
    }

    // ✅ CHAMPION UNLOCK SYSTEM
    public function unlockChampion($champion): array
    {
        // Champion instance ή ID
        if (is_numeric($champion)) {
            $champion = Champion::find($champion);
        }

        if (!$champion) {
            return [
                'success' => false,
                'message' => 'Champion not found'
            ];
        }

        if ($this->hasUnlockedChampion($champion->id)) {
            return [
                'success' => false,
                'message' => 'Champion already unlocked'
            ];
        }

        if ($champion->is_unlocked_by_default) {
            return [
                'success' => false,
                'message' => 'Champion is already available by default'
            ];
        }

        if ($this->points < $champion->unlock_cost) {
            return [
                'success' => false,
                'message' => "Not enough points. Required: {$champion->unlock_cost}, You have: {$this->points}"
            ];
        }

        // Unlock τον champion
        $this->unlockedChampions()->attach($champion->id, [
            'unlocked_at' => now()
        ]);


        $this->decrement('points', $champion->unlock_cost);

        return [
            'success' => true,
            'message' => "Successfully unlocked {$champion->name}!"
        ];
    }

    // ✅ SKIN UNLOCK SYSTEM
    public function unlockSkin($skin): array
    {
        if (is_numeric($skin)) {
            $skin = Skin::find($skin);
        }

        if (!$skin) {
            return [
                'success' => false,
                'message' => 'Skin not found'
            ];
        }

        if ($this->hasUnlockedSkin($skin->id)) {
            return [
                'success' => false,
                'message' => 'Skin already unlocked'
            ];
        }

        if ($skin->is_unlocked_by_default) {
            return [
                'success' => false,
                'message' => 'Skin is already available by default'
            ];
        }

        if ($this->points < $skin->unlock_cost) {
            return [
                'success' => false,
                'message' => "Not enough points. Required: {$skin->unlock_cost}, You have: {$this->points}"
            ];
        }

        // Unlock το skin
        UserUnlock::create([
            'user_id' => $this->id,
            'unlockable_type' => Skin::class,
            'unlockable_id' => $skin->id,
            'cost_paid' => $skin->unlock_cost
        ]);

        $this->decrement('points', $skin->unlock_cost);

        return [
            'success' => true,
            'message' => "Successfully unlocked {$skin->name}!"
        ];
    }

    // ✅ CHECK METHODS
    public function hasUnlockedChampion($championId): bool
    {
        return $this->unlockedChampions()->where('champion_id', $championId)->exists();
    }

    public function hasUnlockedSkin($skinId): bool
    {
        return $this->unlocks()
            ->where('unlockable_type', Skin::class)
            ->where('unlockable_id', $skinId)
            ->exists();
    }

    public function hasUnlocked($item): bool
    {
        if ($item instanceof Champion) {
            return $this->hasUnlockedChampion($item->id);
        } elseif ($item instanceof Skin) {
            return $this->hasUnlockedSkin($item->id);
        }
        return false;
    }

    // ✅ GET UNLOCKED IDS
    public function getUnlockedChampionIds(): array
    {
        return $this->unlockedChampions()->pluck('champion_id')->toArray();
    }

    public function getUnlockedSkinIds(): array
    {
        return $this->unlocks()
            ->where('unlockable_type', Skin::class)
            ->pluck('unlockable_id')
            ->toArray();
    }
}