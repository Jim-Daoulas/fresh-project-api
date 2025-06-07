<?php

namespace App\Http\Controllers;

use App\Helpers\Device;
use App\Models\Champion;
use App\Models\Skin;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function me(Request $request)
    {
        // Log all tokens for debugging
        \Log::debug('Token from header:', ['token' => $request->bearerToken()]);
        
        // Check which tokens exist in the database
        $tokens = \Laravel\Sanctum\PersonalAccessToken::all();
        \Log::debug('All tokens in db:', ['tokens' => $tokens->toArray()]);
        
        // Try to get the user
        $user = $request->user();
        \Log::debug('User after authentication:', ['user' => $user]);
        
        return response()->json([
            'success' => true,
            'message' => 'User retrieved',
            'data' => [
                'user' => $user
            ]
        ]);
    }

    public function tokens(Request $request)
    {
        $tokens = [];
        foreach($request->user()->tokens as $token) {
            $tokens[] = [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at
            ];
        }

        return response()->json([
            'tokens' => $tokens
        ]);
    }

    public function revokeAllTokens(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Tokens revoked, you will be logged out from all devices'
        ]);
    }

    // Progression methods
    public function getProgress(Request $request)
    {
        $user = $request->user();
        $userPoints = $user->getOrCreatePoints();
        $unlockedChampionIds = $user->getUnlockedChampionIds();
        $unlockedSkinIds = $user->getUnlockedSkinIds();
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_points' => $userPoints->total_points,
                'can_claim_daily_bonus' => $user->canClaimDailyBonus(),
                'unlocked_champions_count' => count($unlockedChampionIds),
                'unlocked_skins_count' => count($unlockedSkinIds),
                'total_champions' => Champion::count(),
                'total_skins' => Skin::count(),
                'champion_cost' => User::CHAMPION_COST,
                'skin_cost' => User::SKIN_COST
            ]
        ]);
    }

    public function claimDailyBonus(Request $request)
    {
        $user = $request->user();
        $result = $user->claimDailyBonus();
        
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result
        ]);
    }

    public function getAvailableUnlocks(Request $request)
    {
        $user = $request->user();
        $unlockedChampionIds = $user->getUnlockedChampionIds();
        $unlockedSkinIds = $user->getUnlockedSkinIds();
        $userPoints = $user->getTotalPoints();

        // Get champions not yet unlocked
        $availableChampions = Champion::whereNotIn('id', $unlockedChampionIds)
            ->get()
            ->map(function ($champion) use ($userPoints) {
                return [
                    'id' => $champion->id,
                    'name' => $champion->name,
                    'image_url' => $champion->image_url,
                    'cost' => User::CHAMPION_COST,
                    'can_afford' => $userPoints >= User::CHAMPION_COST
                ];
            });

        // Get skins for unlocked champions that aren't unlocked yet
        $availableSkins = Skin::whereIn('champion_id', $unlockedChampionIds)
            ->whereNotIn('id', $unlockedSkinIds)
            ->with('champion')
            ->get()
            ->map(function ($skin) use ($userPoints) {
                return [
                    'id' => $skin->id,
                    'name' => $skin->name,
                    'champion_name' => $skin->champion->name,
                    'image_url' => $skin->image_url,
                    'cost' => User::SKIN_COST,
                    'can_afford' => $userPoints >= User::SKIN_COST
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'champions' => $availableChampions,
                'skins' => $availableSkins
            ]
        ]);
    }

    public function unlockChampion(Request $request, $championId)
    {
        $user = $request->user();
        $result = $user->unlockChampion($championId);
        
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message']
        ], $result['success'] ? 200 : 400);
    }

    public function unlockSkin(Request $request, $skinId)
    {
        $user = $request->user();
        $result = $user->unlockSkin($skinId);
        
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message']
        ], $result['success'] ? 200 : 400);
    }

    public function trackChampionView(Request $request)
    {
        $user = $request->user();
        $championId = $request->input('champion_id');
        
        if (!$championId) {
            return response()->json([
                'success' => false,
                'message' => 'Champion ID required'
            ], 400);
        }
        
        $user->trackChampionView($championId);
        
        return response()->json([
            'success' => true,
            'message' => 'Champion view tracked'
        ]);
    }

    public function trackComment(Request $request)
    {
        $user = $request->user();
        $user->trackComment();
        
        return response()->json([
            'success' => true,
            'message' => 'Comment tracked'
        ]);
    }
}