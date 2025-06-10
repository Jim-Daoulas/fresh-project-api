<?php

namespace App\Http\Controllers;

use App\Models\Champion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChampionController extends Controller
{
    /**
     * Display a listing of the resource.
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
     * Display the specified resource.
     */
    public function show(Request $request, Champion $champion): JsonResponse
    {
        try {
            \Log::info('ChampionController@show called for champion ID: ' . $champion->id);
            
            $user = $request->user();
            $userId = $user ? $user->id : null;
            
            // Έλεγξε αν ο champion είναι locked
            $isLocked = !$champion->isUnlockedForUser($userId);
            
            if ($isLocked) {
                return response()->json([
                    'success' => false,
                    'message' => 'This champion is locked. Please unlock it first.',
                    'is_locked' => true
                ], 403);
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
     * Unlock a champion for the authenticated user
     */
    public function unlock(Request $request, Champion $champion): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required to unlock champions'
                ], 401);
            }

            // Χρησιμοποίησε την unlockChampion μέθοδο του User model
            $result = $user->unlockChampion($champion);
            
            // Αν δεν πέτυχε το unlock
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
            
            // Επιτυχημένο unlock
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'champion' => [
                        'id' => $champion->id,
                        'name' => $champion->name,
                        'is_locked' => false
                    ],
                    'user_points' => $user->fresh()->points
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in ChampionController@unlock: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock champion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

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
}