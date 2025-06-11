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
                if ($index === 0) {
                    $skin->is_unlocked_by_default = true;
                    $skin->is_locked = false;
                } else {
                    $skin->is_unlocked_by_default = false;
                    // ✅ ΔΙΟΡΘΩΣΗ: Σωστή γραμμή
                    $skin->is_locked = !$user->hasUnlockedSkin($skin->id);
                }

                // Add unlock cost (required by frontend)
                $skin->unlock_cost = $skin->unlock_cost ?? 50;

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
                // ✅ ΔΙΟΡΘΩΣΗ: Σωστά properties
                $skin->is_unlocked_by_default = ($index === 0);
                $skin->is_locked = !($index === 0);  // Αντίστροφη λογική
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

            // ✅ ΔΙΟΡΘΩΣΗ: Χρησιμοποιούμε User method
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

            // ✅ ΔΙΟΡΘΩΣΗ: Απλός έλεγχος points
            if ($user->points < $skin->unlock_cost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not enough points to unlock this skin',
                    'required_points' => $skin->unlock_cost,
                    'user_points' => $user->points
                ], 400);
            }

            // ✅ ΔΙΟΡΘΩΣΗ: Χρησιμοποιούμε τη generic unlock method
            $result = $user->unlock($skin);

            if ($result['success']) {
                // ✅ ΔΙΟΡΘΩΣΗ: Χρησιμοποιούμε fresh()
                $user = $user->fresh();

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'remaining_points' => $user->points
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error unlocking skin: ' . $e->getMessage());

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

            // ✅ ΔΙΟΡΘΩΣΗ: Χρησιμοποιούμε τη μέθοδο που υπάρχει στο User.php
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
                // ✅ ΔΙΟΡΘΩΣΗ: Σωστά properties
                $skin->is_unlocked_by_default = false; // Assume not default unless first
                $skin->is_locked = !$user->hasUnlockedSkin($skin->id);
                $skin->unlock_cost = $skin->unlock_cost ?? 50;
            } else {
                $skin->is_unlocked_by_default = false;
                $skin->is_locked = true;  // Guests see all as locked except defaults
                $skin->unlock_cost = $skin->unlock_cost ?? 50;
            }

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
}