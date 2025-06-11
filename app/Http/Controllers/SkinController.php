<?php

namespace App\Http\Controllers;

use App\Models\Skin;
use App\Models\Champion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SkinController extends Controller
{
    /**
     * Display a listing of skins for a champion
     */
    public function getChampionSkins(Request $request, Champion $champion): JsonResponse
    {
        try {
            $user = $request->user();
            $userId = $user ? $user->id : null;
            
            // Load skins with unlock status
            $skins = $champion->skins->map(function ($skin) use ($userId) {
                $skin->is_locked = !$skin->isUnlockedForUser($userId);
                return $skin;
            });
            
            return response()->json([
                'success' => true,
                'data' => $skins,
                'message' => 'Champion skins retrieved successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in SkinController@getChampionSkins: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch champion skins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified skin with unlock status
     */
    public function show(Request $request, Skin $skin): JsonResponse
    {
        try {
            $user = $request->user();
            $userId = $user ? $user->id : null;
            
            // Check if skin is locked
            $isLocked = !$skin->isUnlockedForUser($userId);
            
            if ($isLocked) {
                return response()->json([
                    'success' => false,
                    'message' => 'This skin is locked. Please unlock it first.',
                    'is_locked' => true,
                    'unlock_cost' => $skin->unlock_cost,
                    'champion_required' => !$skin->champion->isUnlockedForUser($userId)
                ], 403);
            }
            
            $skin->load('champion');
            $skin->is_locked = false;
            
            return response()->json([
                'success' => true,
                'data' => $skin,
                'message' => 'Skin retrieved successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in SkinController@show: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch skin details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unlock a skin for the authenticated user
     */
    public function unlock(Request $request, Skin $skin): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required to unlock skins'
                ], 401);
            }

            // Check if already unlocked
            if ($user->hasUnlockedSkin($skin->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Skin is already unlocked'
                ], 400);
            }

            // Check if it's default unlocked
            if ($skin->is_unlocked_by_default) {
                return response()->json([
                    'success' => false,
                    'message' => 'Skin is already free'
                ], 400);
            }

            // Check if user has enough points
            if ($user->points < $skin->unlock_cost) {
                return response()->json([
                    'success' => false,
                    'message' => "Not enough points. Need {$skin->unlock_cost}, have {$user->points}"
                ], 400);
            }

            // Check if user has unlocked the champion
            if (!$skin->champion->isUnlockedForUser($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must unlock the champion first before unlocking their skins'
                ], 400);
            }

            // Unlock the skin
            $unlocked = $user->unlockSkin($skin->id);
            
            if ($unlocked) {
                return response()->json([
                    'success' => true,
                    'message' => 'Skin unlocked successfully!',
                    'data' => [
                        'skin' => [
                            'id' => $skin->id,
                            'name' => $skin->name,
                            'champion_name' => $skin->champion->name,
                            'is_locked' => false
                        ],
                        'user_points' => $user->fresh()->points
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to unlock skin'
                ], 500);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error in SkinController@unlock: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock skin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Original CRUD methods (for admin/API use)
     */
    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}