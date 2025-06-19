<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use App\Enum\RoleCode;

class User extends Authenticatable implements FilamentUser
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

    // DAILY LOGIN SYSTEM
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

    // COMMENT POINTS SYSTEM
    public function addCommentPoints(): void
    {
        $this->increment('points', 10);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
    public function canAccessPanel(Panel $panel): bool
    {
        // Επέτρεψε πρόσβαση σε users με admin role
        return $this->roles()->where('role_id', RoleCode::admin)->exists();
    }

    // CHAMPION UNLOCK SYSTEM
    public function unlockedChampions(): BelongsToMany
    {
        return $this->belongsToMany(Champion::class, 'champion_unlocks')
            ->withTimestamps()
            ->withPivot('unlocked_at');
    }

    public function unlockChampion($champion): array
    {
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
                'message' => 'Champion is already unlocked'
            ];
        }

        if ($champion->is_unlocked_by_default) {
            return [
                'success' => false,
                'message' => 'This champion is already available by default'
            ];
        }

        if ($this->points < $champion->unlock_cost) {
            return [
                'success' => false,
                'message' => 'Not enough points to unlock this champion'
            ];
        }

        $this->unlockedChampions()->attach($champion->id, [
            'unlocked_at' => now()
        ]);

        $this->decrement('points', $champion->unlock_cost);

        return [
            'success' => true,
            'message' => "Champion '{$champion->name}' unlocked successfully!"
        ];
    }

    public function hasUnlockedChampion($championId): bool
    {
        return $this->unlockedChampions()->where('champion_id', $championId)->exists();
    }

    public function getUnlockedChampionIds(): array
    {
        return $this->unlockedChampions()->pluck('champion_id')->toArray();
    }

    // SKIN UNLOCK SYSTEM
    public function unlockedSkins(): BelongsToMany
    {
        return $this->belongsToMany(Skin::class, 'skin_unlocks')
            ->withTimestamps()
            ->withPivot('unlocked_at');
    }

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

        if ($skin->isUnlockedForUser($this->id)) {
            return [
                'success' => false,
                'message' => 'Skin is already unlocked'
            ];
        }

        // Check if it's the first skin (default unlocked)
        if ($skin->isFirstSkin()) {
            return [
                'success' => false,
                'message' => 'This skin is already available by default'
            ];
        }

        if ($this->points < $skin->unlock_cost) {
            return [
                'success' => false,
                'message' => 'Not enough points to unlock this skin'
            ];
        }

        // Check if user has unlocked the champion first
        if (!$skin->champion->isUnlockedForUser($this->id)) {
            return [
                'success' => false,
                'message' => 'You must unlock the champion first before unlocking their skins'
            ];
        }

        $this->unlockedSkins()->attach($skin->id, [
            'unlocked_at' => now()
        ]);

        $this->decrement('points', $skin->unlock_cost);

        return [
            'success' => true,
            'message' => "Skin '{$skin->name}' unlocked successfully!"
        ];
    }

    public function getUnlockedSkins()
    {
        return $this->unlockedSkins()->with('champion')->get();
    }

    public function hasUnlockedSkin($skinId): bool
    {
        return $this->unlockedSkins()->where('skin_id', $skinId)->exists();
    }

    public function getUnlockedSkinIds(): array
    {
        return $this->unlockedSkins()->pluck('skin_id')->toArray();
    }
}