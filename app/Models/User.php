<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    // Unlock costs
    const CHAMPION_COST = 30;
    const SKIN_COST = 10;
    
    // Points rewards  
    const DAILY_LOGIN_POINTS = 5;
    const VIEW_CHAMPION_POINTS = 2;
    const ADD_COMMENT_POINTS = 1;

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

    // Progression relationships
    public function points(): HasOne
    {
        return $this->hasOne(UserPoints::class);
    }

    public function championUnlocks(): HasMany
    {
        return $this->hasMany(UserChampionUnlock::class);
    }

    public function skinUnlocks(): HasMany
    {
        return $this->hasMany(UserSkinUnlock::class);
    }

    // Progression helper methods
    public function getOrCreatePoints(): UserPoints
    {
        return $this->points ?? $this->points()->create([
            'total_points' => 15, // Start with 15 points
            'last_daily_login' => null
        ]);
    }

    public function getTotalPoints(): int
    {
        return $this->getOrCreatePoints()->total_points;
    }

    public function addPoints(int $points): void
    {
        $userPoints = $this->getOrCreatePoints();
        $userPoints->increment('total_points', $points);
    }

    public function removePoints(int $points): bool
    {
        $userPoints = $this->getOrCreatePoints();
        if ($userPoints->total_points >= $points) {
            $userPoints->decrement('total_points', $points);
            return true;
        }
        return false;
    }

    public function hasUnlockedChampion(int $championId): bool
    {
        return $this->championUnlocks()->where('champion_id', $championId)->exists();
    }

    public function hasUnlockedSkin(int $skinId): bool
    {
        return $this->skinUnlocks()->where('skin_id', $skinId)->exists();
    }

    public function canClaimDailyBonus(): bool
    {
        $userPoints = $this->getOrCreatePoints();
        $today = Carbon::today();
        
        return !$userPoints->last_daily_login || 
               !$userPoints->last_daily_login->equalTo($today);
    }

    public function claimDailyBonus(): array
    {
        if (!$this->canClaimDailyBonus()) {
            return [
                'success' => false,
                'message' => 'Daily bonus already claimed today'
            ];
        }

        $this->addPoints(self::DAILY_LOGIN_POINTS);
        $this->getOrCreatePoints()->update(['last_daily_login' => Carbon::today()]);

        return [
            'success' => true,
            'points_earned' => self::DAILY_LOGIN_POINTS,
            'message' => 'Daily bonus claimed! +' . self::DAILY_LOGIN_POINTS . ' points'
        ];
    }

    public function unlockChampion(int $championId): array
    {
        if ($this->hasUnlockedChampion($championId)) {
            return ['success' => false, 'message' => 'Already unlocked'];
        }

        if ($this->getTotalPoints() < self::CHAMPION_COST) {
            return [
                'success' => false,
                'message' => 'Insufficient points. Need ' . (self::CHAMPION_COST - $this->getTotalPoints()) . ' more points.'
            ];
        }

        if ($this->removePoints(self::CHAMPION_COST)) {
            $this->championUnlocks()->create(['champion_id' => $championId]);
            return ['success' => true, 'message' => 'Champion unlocked!'];
        }

        return ['success' => false, 'message' => 'Failed to unlock champion'];
    }

    public function unlockSkin(int $skinId): array
    {
        if ($this->hasUnlockedSkin($skinId)) {
            return ['success' => false, 'message' => 'Already unlocked'];
        }

        // Check if champion is unlocked
        $skin = Skin::find($skinId);
        if ($skin && !$this->hasUnlockedChampion($skin->champion_id)) {
            return ['success' => false, 'message' => 'Champion not unlocked'];
        }

        if ($this->getTotalPoints() < self::SKIN_COST) {
            return [
                'success' => false,
                'message' => 'Insufficient points. Need ' . (self::SKIN_COST - $this->getTotalPoints()) . ' more points.'
            ];
        }

        if ($this->removePoints(self::SKIN_COST)) {
            $this->skinUnlocks()->create(['skin_id' => $skinId]);
            return ['success' => true, 'message' => 'Skin unlocked!'];
        }

        return ['success' => false, 'message' => 'Failed to unlock skin'];
    }

    public function getUnlockedChampionIds(): array
    {
        return $this->championUnlocks()->pluck('champion_id')->toArray();
    }

    public function getUnlockedSkinIds(): array
    {
        return $this->skinUnlocks()->pluck('skin_id')->toArray();
    }

    public function initializeWithDefaults(): void
    {
        // Give starting points and unlock first champion + skin
        $this->getOrCreatePoints();
        
        $firstChampion = Champion::first();
        if ($firstChampion) {
            $this->championUnlocks()->create(['champion_id' => $firstChampion->id]);
            
            $firstSkin = $firstChampion->skins()->first();
            if ($firstSkin) {
                $this->skinUnlocks()->create(['skin_id' => $firstSkin->id]);
            }
        }
    }

    public function trackChampionView(int $championId): array
    {
        // Only give points once per champion per day
        $today = Carbon::today()->format('Y-m-d');
        $cacheKey = "champion_view_{$this->id}_{$championId}_{$today}";
        
        if (!cache()->has($cacheKey)) {
            $this->addPoints(self::VIEW_CHAMPION_POINTS);
            cache()->put($cacheKey, true, Carbon::today()->endOfDay());
            
            return [
                'points_earned' => self::VIEW_CHAMPION_POINTS,
                'message' => '+' . self::VIEW_CHAMPION_POINTS . ' points for viewing champion!',
                'total_points' => $this->getTotalPoints()
            ];
        }
        
        return [
            'points_earned' => 0, 
            'message' => 'Already viewed today',
            'total_points' => $this->getTotalPoints()
        ];
    }

    public function trackComment(): array
    {
        $this->addPoints(self::ADD_COMMENT_POINTS);
        
        return [
            'points_earned' => self::ADD_COMMENT_POINTS,
            'message' => '+' . self::ADD_COMMENT_POINTS . ' point for commenting!',
            'total_points' => $this->getTotalPoints()
        ];
    }

    // Alternative void methods if you prefer not to return values
    public function trackChampionViewSilent(int $championId): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $cacheKey = "champion_view_{$this->id}_{$championId}_{$today}";
        
        if (!cache()->has($cacheKey)) {
            $this->addPoints(self::VIEW_CHAMPION_POINTS);
            cache()->put($cacheKey, true, Carbon::today()->endOfDay());
        }
    }

    public function trackCommentSilent(): void
    {
        $this->addPoints(self::ADD_COMMENT_POINTS);
    }
}