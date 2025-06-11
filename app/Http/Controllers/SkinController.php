<?php

namespace App\Http\Controllers;

use App\Models\Skin;
use App\Models\Champion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SkinController extends Controller
{
    /**
     * Get all skins for a specific champion (for authenticated users)
     */
    public function getSkinsForChampion($championId)
    {
        try {
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            // Get champion to verify it exists
            $champion = Champion::findOrFail($championId);

            // Get all skins for this champion, ordered by ID (first skin = default)
            $skins = Skin::where('champion_id', $championId)
                ->orderBy('id', 'asc')
                ->get();

            // Add unlock status for each skin
            $skins = $skins->map(function ($skin, $index) use ($user) {
                // First skin is always unlocked by default
                $isFirstSkin = ($index === 0);
                
                $skin->is_unlocked_by_default = $isFirstSkin;
                
                // Check if skin is locked
                if ($isFirstSkin) {
                    $skin->is_locked = false;
                } else {
                    $skin->is_locked = !$user->hasUnlockedSkin($skin->id);
                }

                // Add unlock cost (required by frontend)
                $skin->unlock_cost = $skin->unlock_cost ?? 50;

                // Add unlock status info
                $skin->can_unlock = !$isFirstSkin && $skin->is_locked && ($user->points >= $skin->unlock_cost);
                $skin->user_points = $user->points;

                return $skin;
            });

            return response()->json([
                'success' => true,
                'data' => $skins,
                'champion' => $champion->name,
                'message' => 'Skins retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching skins for champion: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching skins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get skins for guests (only default unlocked skins)
     */
    public function getPublicSkinsForChampion($championId)
    {
        try {
            $champion = Champion::findOrFail($championId);

            // Get all skins, but mark only first as unlocked for guests
            $skins = Skin::where('champion_id', $championId)
                ->orderBy('id', 'asc')
                ->get();

            $skins = $skins->map(function ($skin, $index) {
                // Only first skin is unlocked for guests
                $isFirstSkin = ($index === 0);
                
                $skin->is_locked = !$isFirstSkin;
                $skin->is_unlocked_by_default = $isFirstSkin;
                $skin->can_unlock = false;
                $skin->user_points = 0;
                $skin->unlock_cost = $skin->unlock_cost ?? 50;

                return $skin;
            });

            return response()->json([
                'success' => true,
                'data' => $skins,
                'champion' => $champion->name,
                'message' => 'Public skins retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching public skins: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching skins'
            ], 500);
        }
    }

    /**
     * Unlock a skin for the authenticated user
     */
    public function unlock(Request $request, $skinId)
    {
        try {
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $skin = Skin::findOrFail($skinId);

            // Check if already unlocked
            if ($user->hasUnlockedSkin($skin->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Skin is already unlocked'
                ], 400);
            }

            // Check if it's the first skin (default unlocked)
            $firstSkin = Skin::where('champion_id', $skin->champion_id)
                ->orderBy('id', 'asc')
                ->first();

            if ($skin->id === $firstSkin->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This skin is already available by default'
                ], 400);
            }

            // Check if user has enough points
            if ($user->points < $skin->unlock_cost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not enough points to unlock this skin',
                    'required_points' => $skin->unlock_cost,
                    'user_points' => $user->points
                ], 400);
            }

            // ✅ ΔΙΟΡΘΩΣΗ: Χρησιμοποιούμε τη σωστή μέθοδο
            $success = $user->unlockSkin($skin->id);

            if ($success) {
                // Refresh user data
                $user = $user->fresh();

                return response()->json([
                    'success' => true,
                    'message' => "Skin '{$skin->name}' unlocked successfully!",
                    'remaining_points' => $user->points,
                    'data' => [
                        'skin_id' => $skin->id,
                        'skin_name' => $skin->name,
                        'user_points' => $user->points
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to unlock skin. You may need to unlock the champion first.'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error unlocking skin: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error unlocking skin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all unlocked skins for the authenticated user
     */
    public function getUserUnlockedSkins()
    {
        try {
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $unlockedSkins = $user->getUnlockedSkins();

            return response()->json([
                'success' => true,
                'data' => $unlockedSkins,
                'total_unlocked' => $unlockedSkins->count(),
                'message' => 'Unlocked skins retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching user unlocked skins: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching unlocked skins'
            ], 500);
        }
    }

    /**
     * Display a listing of all skins (admin/debug purpose)
     */
    public function index()
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
            Log::error('Error fetching all skins: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching skins'
            ], 500);
        }
    }

    /**
     * Display the specified skin
     */
    public function show($id)
    {
        try {
            $user = Auth::guard('sanctum')->user();
            $skin = Skin::with('champion')->findOrFail($id);

            // Add unlock status if user is authenticated
            if ($user) {
                // Check if it's the first skin (default unlocked)
                $firstSkin = Skin::where('champion_id', $skin->champion_id)
                    ->orderBy('id', 'asc')
                    ->first();
                
                $isFirstSkin = ($skin->id === $firstSkin->id);
                
                if ($isFirstSkin) {
                    $skin->is_locked = false;
                    $skin->is_unlocked_by_default = true;
                } else {
                    $skin->is_locked = !$user->hasUnlockedSkin($skin->id);
                    $skin->is_unlocked_by_default = false;
                }
                
                $skin->can_unlock = !$isFirstSkin && $skin->is_locked && ($user->points >= $skin->unlock_cost);
                $skin->user_points = $user->points;
            } else {
                // For guests, only first skin is unlocked
                $firstSkin = Skin::where('champion_id', $skin->champion_id)
                    ->orderBy('id', 'asc')
                    ->first();
                
                $isFirstSkin = ($skin->id === $firstSkin->id);
                
                $skin->is_locked = !$isFirstSkin;
                $skin->is_unlocked_by_default = $isFirstSkin;
                $skin->can_unlock = false;
                $skin->user_points = 0;
            }

            // Ensure unlock_cost is set
            $skin->unlock_cost = $skin->unlock_cost ?? 50;

            return response()->json([
                'success' => true,
                'data' => $skin,
                'message' => 'Skin retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching skin: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching skin'
            ], 500);
        }
    }

    /**
     * Store a newly created skin in storage (admin only)
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'champion_id' => 'required|exists:champions,id',
                'image_url' => 'required|url',
                'unlock_cost' => 'nullable|integer|min:0',
                'description' => 'nullable|string'
            ]);

            $skin = Skin::create([
                'name' => $request->name,
                'champion_id' => $request->champion_id,
                'image_url' => $request->image_url,
                'unlock_cost' => $request->unlock_cost ?? 50,
                'description' => $request->description
            ]);

            return response()->json([
                'success' => true,
                'data' => $skin->load('champion'),
                'message' => 'Skin created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating skin: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating skin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified skin in storage (admin only)
     */
    public function update(Request $request, $id)
    {
        try {
            $skin = Skin::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|string|max:255',
                'champion_id' => 'sometimes|exists:champions,id',
                'image_url' => 'sometimes|url',
                'unlock_cost' => 'sometimes|integer|min:0',
                'description' => 'sometimes|string'
            ]);

            $skin->update($request->only([
                'name', 'champion_id', 'image_url', 'unlock_cost', 'description'
            ]));

            return response()->json([
                'success' => true,
                'data' => $skin->load('champion'),
                'message' => 'Skin updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating skin: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating skin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified skin from storage (admin only)
     */
    public function destroy($id)
    {
        try {
            $skin = Skin::findOrFail($id);
            
            // Remove all unlock records for this skin
            $skin->users()->detach();
            
            $skin->delete();

            return response()->json([
                'success' => true,
                'message' => 'Skin deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting skin: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting skin',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}