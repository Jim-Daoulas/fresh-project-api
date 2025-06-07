<?php

namespace App\Http\Controllers;

use App\Helpers\Device;
use App\Models\Champion;
use App\Models\Skin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Load user with progression data
            $user->load(['points', 'championUnlocks', 'skinUnlocks', 'roles']);
            
            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully',
                'data' => [
                    'user' => $user,
                    'progression' => $user->getProgressStats()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in UserController@me: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function tokens(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $tokens = [];
            
            foreach($user->tokens as $token) {
                $tokens[] = [
                    'id' => $token->id,
                    'name' => $token->name,
                    'last_used_at' => $token->last_used_at
                ];
            }

            return response()->json([
                'success' => true,
                'data' => ['tokens' => $tokens]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tokens',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function revokeAllTokens(Request $request): JsonResponse
    {
        try {
            $request->user()->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'All tokens revoked successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke tokens',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Progression methods
    public function getProgress(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $progressStats = $user->getProgressStats();
            
            return response()->json([
                'success' => true,
                'message' => 'Progress retrieved successfully',
                'data' => $progressStats
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching user progress: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function claimDailyBonus(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $result = $user->claimDailyBonus();
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result
            ], $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            \Log::error('Error claiming daily bonus: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to claim daily bonus',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableUnlocks(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $unlockedChampionIds = $user->getUnlockedChampionIds();
            $unlockedSkinIds = $user->getUnlockedSkinIds();
            $userPoints = $user->getTotalPoints();

            // Get champions not yet unlocked
            $availableChampions = Champion::whereNotIn('id', $unlockedChampionIds)
                ->get()
                ->map(function ($champion) use ($userPoints) {
                    return [
                        'id' => $champion->id,
                        'name' => $champion->name,
                        'title' => $champion->title,
                        'role' => $champion->role,
                        'image_url' => $champion->image_url,
                        'cost' => User::CHAMPION_COST,
                        'can_afford' => $userPoints >= User::CHAMPION_COST
                    ];
                });

            // Get skins for unlocked champions that aren't unlocked yet
            $availableSkins = Skin::whereIn('champion_id', $unlockedChampionIds)
                ->whereNotIn('id', $unlockedSkinIds)
                ->with('champion')
                ->get()
                ->map(function ($skin) use ($userPoints) {
                    return [
                        'id' => $skin->id,
                        'name' => $skin->name,
                        'champion_name' => $skin->champion->name,
                        'champion_id' => $skin->champion_id,
                        'image_url' => $skin->image_url,
                        'cost' => User::SKIN_COST,
                        'can_afford' => $userPoints >= User::SKIN_COST
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Available unlocks retrieved successfully',
                'data' => [
                    'current_points' => $userPoints,
                    'champions' => $availableChampions,
                    'skins' => $availableSkins,
                    'costs' => [
                        'champion' => User::CHAMPION_COST,
                        'skin' => User::SKIN_COST
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching available unlocks: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available unlocks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unlockChampion(Request $request, $championId): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Validate champion ID
            if (!is_numeric($championId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid champion ID'
                ], 400);
            }

            $result = $user->unlockChampion((int)$championId);
            
            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            \Log::error('Error unlocking champion: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock champion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unlockSkin(Request $request, $skinId): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Validate skin ID
            if (!is_numeric($skinId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid skin ID'
                ], 400);
            }

            $result = $user->unlockSkin((int)$skinId);
            
            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            \Log::error('Error unlocking skin: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock skin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function trackChampionView(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $validated = $request->validate([
                'champion_id' => 'required|integer|exists:champions,id'
            ]);
            
            $championId = $validated['champion_id'];
            
            // Check if champion is unlocked
            if (!$user->hasUnlockedChampion($championId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Champion not unlocked'
                ], 403);
            }
            
            $result = $user->trackChampionView($championId);
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'points_earned' => $result['points_earned'],
                    'total_points' => $result['total_points']
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error tracking champion view: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to track champion view',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function trackComment(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $result = $user->trackComment();
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'points_earned' => $result['points_earned'],
                    'total_points' => $result['total_points']
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error tracking comment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to track comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}