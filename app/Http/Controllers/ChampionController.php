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
            \Log::info('ChampionController@index called');
            
            $user = $request->user();
            $userId = $user ? $user->id : null;
            
            $champions = Champion::all();
            
            // Προσθέτουμε το is_locked για κάθε champion
            $champions = $champions->map(function ($champion) use ($userId) {
                $champion->is_locked = !$champion->isUnlockedForUser($userId);
                return $champion;
            });
            
            \Log::info('Champions count: ' . $champions->count());
            
            return response()->json([
                'success' => true,
                'data' => $champions,
                'message' => 'Champions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in ChampionController@index: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
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

            // Έλεγξε αν ήδη είναι unlocked
            if ($champion->isUnlockedForUser($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Champion is already unlocked'
                ], 400);
            }

            // Unlock τον champion
            $unlocked = $user->unlockChampion($champion->id);
            
            if ($unlocked) {
                return response()->json([
                    'success' => true,
                    'message' => 'Champion unlocked successfully!',
                    'champion' => [
                        'id' => $champion->id,
                        'name' => $champion->name,
                        'is_locked' => false
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to unlock champion'
                ], 500);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error in ChampionController@unlock: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock champion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test endpoint να δούμε αν φτάνει το request
     */
    public function test(): JsonResponse
    {
        \Log::info('Test endpoint called');
        
        return response()->json([
            'success' => true,
            'message' => 'Test endpoint working!',
            'timestamp' => now()
        ]);
    }
}