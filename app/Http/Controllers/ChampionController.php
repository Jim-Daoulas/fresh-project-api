<?php

namespace App\Http\Controllers;

use App\Models\Champion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChampionController extends Controller
{
    /**
     * Display a listing of the resource (for authenticated users).
     */
    public function index(Request $request): JsonResponse
    {
        try {
            \Log::info('=== ChampionController@index DEBUG ===');

            $user = $request->user();
            $userId = $user ? $user->id : null;

            \Log::info("User: " . ($user ? $user->email : 'null'));
            \Log::info("User ID: " . ($userId ?? 'null'));

            $champions = Champion::all();

            // Debug για κάθε champion
            $champions = $champions->map(function ($champion) use ($userId) {
                $isUnlocked = $champion->isUnlockedForUser($userId);
                $isLocked = !$isUnlocked;

                \Log::info("Champion {$champion->id} ({$champion->name}): unlocked=" . ($isUnlocked ? 'true' : 'false') . ", locked=" . ($isLocked ? 'true' : 'false'));

                $champion->is_locked = $isLocked;
                return $champion;
            });

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

    /**
     * Display a listing for guests (public champions).
     */
    public function publicIndex(): JsonResponse
    {
        try {
            $champions = Champion::all();

            // For guests: only show default unlocked champions (IDs 1, 2, 3)
            $defaultUnlockedIds = [1, 2, 3];

            $champions = $champions->map(function ($champion) use ($defaultUnlockedIds) {
                $champion->is_locked = !in_array($champion->id, $defaultUnlockedIds);
                return $champion;
            });

            return response()->json([
                'success' => true,
                'data' => $champions,
                'message' => 'Public champions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in ChampionController@publicIndex: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch champions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource (for authenticated users).
     */
    public function show(Request $request, Champion $champion): JsonResponse
    {
        try {
            \Log::info('ChampionController@show called for champion ID: ' . $champion->id);

            $user = $request->user();
            $userId = $user ? $user->id : null;

            // Έλεγξε αν ο champion είναι locked
            $isLocked = !$champion->isUnlockedForUser($userId);
            \Log::info('Champion unlock check:', [
                'champion_id' => $champion->id,
                'user_id' => $userId,
                'is_locked' => $isLocked
            ]);

            // ✅ FIX: Επέστρεψε πάντα 200, αλλά με lock info
            if ($isLocked) {
                return response()->json([
                    'success' => true, // ✅ Changed to true
                    'data' => [
                        'id' => $champion->id,
                        'name' => $champion->name,
                        'title' => $champion->title,
                        'role' => $champion->role,
                        'image_url' => $champion->image_url,
                        'unlock_cost' => $champion->unlock_cost,
                        'is_locked' => true
                    ],
                    'message' => 'This champion is locked. Please unlock it first.',
                    'is_locked' => true
                ], 200); // ✅ Changed to 200
            }

            $champion->load([
                'abilities',
                'skins',
                'rework.abilities',
                'rework.comments.user'
            ]);

            $champion->is_locked = false;

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

    /**
     * Show champion details for guests (only default unlocked champions)
     */
    public function showPublic(Request $request, Champion $champion): JsonResponse
    {
        try {
            \Log::info('=== ChampionController@showPublic DEBUG ===');
            \Log::info('Champion ID: ' . $champion->id);
            \Log::info('Champion Name: ' . $champion->name);
            \Log::info('is_unlocked_by_default: ' . ($champion->is_unlocked_by_default ? 'true' : 'false'));
            
            // For guests: only show default unlocked champions (IDs 1, 2, 3)
            $defaultUnlockedIds = [1, 2, 3];
            $isDefaultUnlocked = in_array($champion->id, $defaultUnlockedIds) || $champion->is_unlocked_by_default;
            
            if (!$isDefaultUnlocked) {
                \Log::info('Champion is NOT default unlocked - returning locked data');
                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $champion->id,
                        'name' => $champion->name,
                        'title' => $champion->title,
                        'role' => $champion->role,
                        'region' => $champion->region,
                        'description' => $champion->description,
                        'image_url' => $champion->image_url,
                        'unlock_cost' => $champion->unlock_cost,
                        'is_locked' => true
                    ],
                    'message' => 'This champion is locked. Please log in to unlock it.',
                    'is_locked' => true
                ], 200);
            }
            
            \Log::info('Champion is default unlocked - loading full data');
            $champion->load([
                'abilities', 
                'skins', 
                'rework.abilities',
                'rework.comments.user'
            ]);
            
            $champion->is_locked = false;
            
            return response()->json([
                'success' => true,
                'data' => $champion,
                'message' => 'Champion retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in ChampionController@showPublic: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch champion details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}