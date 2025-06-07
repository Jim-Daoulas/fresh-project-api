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
                // For guests, show first champion as preview, rest as locked
                $champions = $champions->map(function ($champion, $index) {
                    $champion->is_unlocked = $index === 0; 
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
            $viewResult = null;
            
            // Check if user has unlocked this champion
            if ($user) {
                $champion->is_unlocked = $user->hasUnlockedChampion($champion->id);
                
                // Track champion view for points only if unlocked
                if ($champion->is_unlocked) {
                    $viewResult = $user->trackChampionView($champion->id);
                }
            } else {
                // For guests, only first champion is viewable
                $champion->is_unlocked = $champion->id === Champion::first()?->id;
            }
            
            $champion->load([
                'abilities', 
                'skins', 
                'rework.abilities',
                'rework.comments.user'
            ]);
            
            // Filter skins based on user unlocks
            if ($user && $champion->is_unlocked) {
                $unlockedSkinIds = $user->getUnlockedSkinIds();
                $champion->skins = $champion->skins->map(function ($skin) use ($unlockedSkinIds) {
                    $skin->is_unlocked = in_array($skin->id, $unlockedSkinIds);
                    return $skin;
                });
            } else {
                // For locked champions or guests, show skins as locked
                $champion->skins = $champion->skins->map(function ($skin) {
                    $skin->is_unlocked = false;
                    return $skin;
                });
            }
            
            $responseData = [
                'success' => true,
                'data' => $champion,
                'message' => 'Champion retrieved successfully'
            ];

            // Add progression info if points were earned
            if ($user && $viewResult && $viewResult['points_earned'] > 0) {
                $responseData['progression'] = [
                    'points_earned' => $viewResult['points_earned'],
                    'message' => $viewResult['message'],
                    'total_points' => $viewResult['total_points']
                ];
            }
            
            return response()->json($responseData);
        } catch (\Exception $e) {
            \Log::error('Error in ChampionController@show: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch champion details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getChampionsByRole(Request $request, string $role): JsonResponse
    {
        try {
            $user = $request->user();
            $champions = Champion::where('role', $role)->get();
            
            if ($user) {
                $unlockedChampionIds = $user->getUnlockedChampionIds();
                
                $champions = $champions->map(function ($champion) use ($unlockedChampionIds) {
                    $champion->is_unlocked = in_array($champion->id, $unlockedChampionIds);
                    return $champion;
                });
            } else {
                $champions = $champions->map(function ($champion, $index) {
                    $champion->is_unlocked = $index === 0;
                    return $champion;
                });
            }
            
            return response()->json([
                'success' => true,
                'data' => $champions,
                'message' => "Champions with role '{$role}' retrieved successfully"
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in ChampionController@getChampionsByRole: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch champions by role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2|max:50'
            ]);

            $query = $request->input('query');
            $user = $request->user();
            
            $champions = Champion::where('name', 'LIKE', "%{$query}%")
                ->orWhere('title', 'LIKE', "%{$query}%")
                ->orWhere('role', 'LIKE', "%{$query}%")
                ->orWhere('region', 'LIKE', "%{$query}%")
                ->get();
            
            if ($user) {
                $unlockedChampionIds = $user->getUnlockedChampionIds();
                
                $champions = $champions->map(function ($champion) use ($unlockedChampionIds) {
                    $champion->is_unlocked = in_array($champion->id, $unlockedChampionIds);
                    return $champion;
                });
            } else {
                $champions = $champions->map(function ($champion, $index) {
                    $champion->is_unlocked = $index === 0;
                    return $champion;
                });
            }
            
            return response()->json([
                'success' => true,
                'data' => $champions,
                'message' => "Search results for '{$query}'"
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error in ChampionController@search: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get champion unlock status for a specific user
     */
    public function getUnlockStatus(Request $request, Champion $champion): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $isUnlocked = $user->hasUnlockedChampion($champion->id);
            $userPoints = $user->getTotalPoints();
            $canAfford = $userPoints >= $user::CHAMPION_COST;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'champion_id' => $champion->id,
                    'champion_name' => $champion->name,
                    'is_unlocked' => $isUnlocked,
                    'cost' => $user::CHAMPION_COST,
                    'user_points' => $userPoints,
                    'can_afford' => $canAfford && !$isUnlocked
                ],
                'message' => 'Champion unlock status retrieved'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting champion unlock status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unlock status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test endpoint για debugging
     */
    public function test(): JsonResponse
    {
        \Log::info('Champion test endpoint called');
        
        return response()->json([
            'success' => true,
            'message' => 'Champion controller test endpoint working!',
            'timestamp' => now(),
            'endpoints' => [
                'GET /champions/champions' => 'List all champions',
                'GET /champions/{id}' => 'Show specific champion',
                'GET /champions/role/{role}' => 'Champions by role',
                'GET /champions/search?query=...' => 'Search champions',
                'GET /champions/{id}/unlock-status' => 'Check unlock status'
            ]
        ]);
    }
}