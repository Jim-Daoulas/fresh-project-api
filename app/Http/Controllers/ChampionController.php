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
        if ($user && !$user->hasUnlockedChampion($champion->id)) {
            // Champion not unlocked, but still show it with limited access
            $champion->is_unlocked = false;
        } else {
            $champion->is_unlocked = true;
        }
        
        $champion->load([
            'abilities', 
            'skins', 
            'rework.abilities',
            'rework.comments.user'
        ]);
        
        // Filter skins and track only if champion is unlocked
        if ($user) {
            $unlockedSkinIds = $user->getUnlockedSkinIds();
            $champion->skins = $champion->skins->map(function ($skin) use ($unlockedSkinIds, $champion) {
                $skin->is_unlocked = $champion->is_unlocked && in_array($skin->id, $unlockedSkinIds);
                return $skin;
            });
            
            // Track champion view for points only if unlocked
            if ($champion->is_unlocked) {
                $user->trackChampionView($champion->id);
            }
        } else {
            $champion->is_unlocked = false;
            $champion->skins = $champion->skins->map(function ($skin) {
                $skin->is_unlocked = false;
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