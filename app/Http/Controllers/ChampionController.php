<?php

namespace App\Http\Controllers;

use App\Models\Champion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChampionController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $champions = Champion::all();
            
            if ($user) {
                $unlockedChampionIds = $user->getUnlockedChampionIds();
                
                $champions = $champions->map(function ($champion) use ($unlockedChampionIds) {
                    $champion->is_unlocked = in_array($champion->id, $unlockedChampionIds);
                    return $champion;
                });
            } else {
                // For guests, show all as locked except first one (preview)
                $champions = $champions->map(function ($champion, $index) {
                    $champion->is_unlocked = $index === 0; // Only first champion visible for guests
                    return $champion;
                });
            }
            
            return response()->json([
                'success' => true,
                'data' => $champions,
                'message' => 'Champions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in ChampionController@index: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch champions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, Champion $champion): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Check if user has unlocked this champion
            if ($user) {
                if (!$user->hasUnlockedChampion($champion->id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Champion not unlocked',
                        'champion_id' => $champion->id,
                        'unlock_cost' => $user::CHAMPION_COST,
                        'user_points' => $user->getTotalPoints(),
                        'can_unlock' => $user->getTotalPoints() >= $user::CHAMPION_COST
                    ], 403);
                }
                
                // Track champion view for points (only for unlocked champions)
                $user->trackChampionView($champion->id);
            } else {
                // Guests can only view the first champion
                if ($champion->id !== Champion::first()?->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please login to view this champion'
                    ], 401);
                }
            }
            
            $champion->load([
                'abilities', 
                'skins', 
                'rework.abilities',
                'rework.comments.user'
            ]);
            
            // Filter skins based on user unlocks
            if ($user) {
                $unlockedSkinIds = $user->getUnlockedSkinIds();
                $champion->skins = $champion->skins->map(function ($skin) use ($unlockedSkinIds) {
                    $skin->is_unlocked = in_array($skin->id, $unlockedSkinIds);
                    return $skin;
                });
            } else {
                // For guests, show only first skin as unlocked
                $champion->skins = $champion->skins->map(function ($skin, $index) {
                    $skin->is_unlocked = $index === 0;
                    return $skin;
                });
            }
            
            return response()->json([
                'success' => true,
                'data' => $champion,
                'message' => 'Champion retrieved successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in ChampionController@show: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch champion details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}