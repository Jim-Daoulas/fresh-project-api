<?php

namespace App\Http\Controllers;

use App\Models\Skin;
use App\Models\Champion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SkinController extends Controller
{
    /**
     * Get all skins for a specific champion (for authenticated users)
     */
    public function getSkinsForChampion(Request $request, $championId): JsonResponse
    {
        try {
            \Log::info('=== SkinController@getSkinsForChampion DEBUG ===');

            $user = $request->user();
            $userId = $user ? $user->id : null;

            \Log::info("User: " . ($user ? $user->email : 'null'));
            \Log::info("Champion ID: " . $championId);

            // Get champion to verify it exists
            $champion = Champion::findOrFail($championId);

            // Get all skins for this champion, ordered by ID (first skin = default)
            $skins = Skin::where('champion_id', $championId)
                ->orderBy('id', 'asc')
                ->get();

            // Add unlock status for each skin
            $skins = $skins->map(function ($skin, $index) use ($userId) {
                $isUnlocked = $skin->isUnlockedForUser($userId);
                $isLocked = !$isUnlocked;

                \Log::info("Skin {$skin->id} ({$skin->name}): unlocked=" . ($isUnlocked ? 'true' : 'false') . ", locked=" . ($isLocked ? 'true' : 'false'));

                $skin->is_locked = $isLocked;
                return $skin;
            });

            return response()->json([
                'success' => true,
                'data' => $skins,
                'champion' => $champion->name,
                'message' => 'Skins retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in SkinController@getSkinsForChampion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch skins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get skins for guests (only default unlocked skins)
     */
    public function getPublicSkinsForChampion(Request $request, $championId): JsonResponse
    {
        try {
            $champion = Champion::findOrFail($championId);

            $skins = Skin::where('champion_id', $championId)
                ->orderBy('id', 'asc')
                ->get();

            // For guests: only first skin is unlocked by default
            $skins = $skins->map(function ($skin, $index) {
                $skin->is_locked = ($index !== 0); // First skin (index 0) is unlocked
                return $skin;
            });

            return response()->json([
                'success' => true,
                'data' => $skins,
                'champion' => $champion->name,
                'message' => 'Public skins retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in SkinController@getPublicSkinsForChampion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch skins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified skin (for authenticated users)
     */
    public function show(Request $request, Skin $skin): JsonResponse
    {
        try {
            \Log::info('SkinController@show called for skin ID: ' . $skin->id);

            $user = $request->user();
            $userId = $user ? $user->id : null;

            // Check if the skin is locked
            $isLocked = !$skin->isUnlockedForUser($userId);
            \Log::info('Skin unlock check:', [
                'skin_id' => $skin->id,
                'user_id' => $userId,
                'is_locked' => $isLocked
            ]);

            //  Always return 200, but with lock info
            if ($isLocked) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $skin->id,
                        'name' => $skin->name,
                        'champion_id' => $skin->champion_id,
                        'image_url' => $skin->image_url,
                        'unlock_cost' => $skin->unlock_cost,
                        'is_locked' => true
                    ],
                    'message' => 'This skin is locked. Please unlock it first.',
                    'is_locked' => true
                ], 200);
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
     * Show skin details for guests (only default unlocked skins)
     */
    public function showPublic(Request $request, Skin $skin): JsonResponse
    {
        try {
            \Log::info('=== SkinController@showPublic DEBUG ===');
            \Log::info('Skin ID: ' . $skin->id);
            \Log::info('Skin Name: ' . $skin->name);

            // For guests: only first skin of each champion is unlocked
            $firstSkin = Skin::where('champion_id', $skin->champion_id)
                ->orderBy('id', 'asc')
                ->first();

            $isDefaultUnlocked = ($skin->id === $firstSkin->id);

            if (!$isDefaultUnlocked) {
                \Log::info('Skin is NOT default unlocked - returning locked data');
                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $skin->id,
                        'name' => $skin->name,
                        'champion_id' => $skin->champion_id,
                        'image_url' => $skin->image_url,
                        'unlock_cost' => $skin->unlock_cost,
                        'is_locked' => true
                    ],
                    'message' => 'This skin is locked. Please log in to unlock it.',
                    'is_locked' => true
                ], 200);
            }

            \Log::info('Skin is default unlocked - loading full data');
            $skin->load('champion');
            $skin->is_locked = false;

            return response()->json([
                'success' => true,
                'data' => $skin,
                'message' => 'Skin retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in SkinController@showPublic: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch skin details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all unlocked skins for the authenticated user
     */
    public function getUserUnlockedSkins(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $unlockedSkins = $user->unlockedSkins()->with('champion')->get();

            return response()->json([
                'success' => true,
                'data' => $unlockedSkins,
                'total_unlocked' => $unlockedSkins->count(),
                'message' => 'Unlocked skins retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in SkinController@getUserUnlockedSkins: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching unlocked skins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of all skins (admin/debug purpose)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $skins = Skin::with('champion')->orderBy('champion_id')->orderBy('id')->get();

            return response()->json([
                'success' => true,
                'data' => $skins,
                'total' => $skins->count(),
                'message' => 'All skins retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in SkinController@index: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching skins',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}