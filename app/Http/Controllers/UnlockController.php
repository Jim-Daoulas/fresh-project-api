<?php

namespace App\Http\Controllers;

use App\Models\Champion;
use App\Models\Skin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UnlockController extends Controller
{
    /**
     * Get user's unlock status and points
     */
    public function getUserProgress(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'points' => $user->points,
                    'unlocked_champions_count' => $user->unlocks()->champions()->count(),
                    'unlocked_skins_count' => $user->unlocks()->skins()->count(),
                    'unlocked_champion_ids' => $user->getUnlockedChampionIds(),
                    'unlocked_skin_ids' => $user->getUnlockedSkinIds(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user progress'
            ], 500);
        }
    }

    /**
     * Unlock a champion
     */
    public function unlockChampion(Request $request, Champion $champion): JsonResponse
    {
        try {
            $user = $request->user();
            $result = $user->unlock($champion);
            
            $statusCode = $result['success'] ? 200 : 400;
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'champion_id' => $champion->id,
                    'champion_name' => $champion->name,
                    'cost' => $champion->unlock_cost,
                    'user_points' => $user->fresh()->points,
                ]
            ], $statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock champion'
            ], 500);
        }
    }

    /**
     * Unlock a skin
     */
    public function unlockSkin(Request $request, Skin $skin): JsonResponse
    {
        try {
            $user = $request->user();
            $result = $user->unlock($skin);
            
            $statusCode = $result['success'] ? 200 : 400;
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'skin_id' => $skin->id,
                    'skin_name' => $skin->name,
                    'champion_name' => $skin->champion->name,
                    'cost' => $skin->unlock_cost,
                    'user_points' => $user->fresh()->points,
                ]
            ], $statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock skin'
            ], 500);
        }
    }

    /**
     * Get available items to unlock
     */
    public function getAvailableUnlocks(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Champions που μπορεί να unlock
            $availableChampions = Champion::lockedForUser($user)
                ->where('unlock_cost', '<=', $user->points)
                ->select(['id', 'name', 'title', 'image_url', 'unlock_cost'])
                ->get();

            // Skins που μπορεί να unlock
            $availableSkins = Skin::lockedForUser($user)
                ->where('unlock_cost', '<=', $user->points)
                ->with('champion:id,name')
                ->select(['id', 'champion_id', 'name', 'image_url', 'unlock_cost'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'user_points' => $user->points,
                    'available_champions' => $availableChampions,
                    'available_skins' => $availableSkins,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available unlocks'
            ], 500);
        }
    }

    /**
     * Get all locked items (regardless of user points)
     */
    public function getLockedItems(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Όλοι οι locked champions
            $lockedChampions = Champion::lockedForUser($user)
                ->select(['id', 'name', 'title', 'image_url', 'unlock_cost'])
                ->get();

            // Όλα τα locked skins
            $lockedSkins = Skin::lockedForUser($user)
                ->with('champion:id,name')
                ->select(['id', 'champion_id', 'name', 'image_url', 'unlock_cost'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'user_points' => $user->points,
                    'locked_champions' => $lockedChampions,
                    'locked_skins' => $lockedSkins,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch locked items'
            ], 500);
        }
    }

    /**
     * Add points to user (για testing ή admin functionality)
     */
    public function addPoints(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1|max:1000'
        ]);

        try {
            $user = $request->user();
            $user->addPoints($request->amount);
            
            return response()->json([
                'success' => true,
                'message' => "Added {$request->amount} points",
                'data' => [
                    'points' => $user->points
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add points'
            ], 500);
        }
    }
}