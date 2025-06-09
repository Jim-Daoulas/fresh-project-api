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
        
        $champions = Champion::with(['skins', 'abilities'])->get();
        
        // Convert to array for easy manipulation
        $championsArray = $champions->toArray();
        
        // Add unlock status
        if ($request->user()) {
            $user = $request->user();
            error_log('=== GETTING UNLOCKED CHAMPIONS ===');
        error_log('User ID: ' . $user->id);
            $unlockedChampionIds = $user->getUnlockedChampionIds();
            error_log('Unlocked champion IDs: ' . json_encode($unlockedChampionIds));
            $unlockedSkinIds = $user->getUnlockedSkinIds();
            error_log('Unlocked skin IDs: ' . json_encode($unlockedSkinIds));
            
            \Log::info('User ' . $user->id . ' unlocked champions: ' . json_encode($unlockedChampionIds));
            \Log::info('User points: ' . $user->points);
            
            foreach ($championsArray as &$champion) {
                // Add unlock status to champion
                $champion['user_has_unlocked'] = $champion['is_unlocked_by_default'] || 
                                               in_array($champion['id'], $unlockedChampionIds);
                
                // ✅ FIXED: Check if user can afford + not already unlocked
                $champion['user_can_unlock'] = !$champion['user_has_unlocked'] && 
                                             $user->points >= $champion['unlock_cost'];
                
                // Add unlock status to skins
                if (isset($champion['skins']) && is_array($champion['skins'])) {
                    foreach ($champion['skins'] as &$skin) {
                        $skin['user_has_unlocked'] = $skin['is_unlocked_by_default'] || 
                                                    in_array($skin['id'], $unlockedSkinIds);
                        
                        // ✅ FIXED: Check if user can afford skin
                        $skin['user_can_unlock'] = !$skin['user_has_unlocked'] && 
                                                  $user->points >= $skin['unlock_cost'];
                    }
                }
            }
        } else {
            // ✅ FIXED: Guest users should see some champions as unlocked by default
            foreach ($championsArray as &$champion) {
                $champion['user_has_unlocked'] = $champion['is_unlocked_by_default'];
                $champion['user_can_unlock'] = false; // Guests can't unlock
                
                if (isset($champion['skins']) && is_array($champion['skins'])) {
                    foreach ($champion['skins'] as &$skin) {
                        $skin['user_has_unlocked'] = $skin['is_unlocked_by_default'];
                        $skin['user_can_unlock'] = false;
                    }
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $championsArray,
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
            // Πρόσθεσε αυτό:
        \Log::info('Auth check:', [
            'has_user' => $request->user() ? 'YES' : 'NO',
            'user_id' => $request->user() ? $request->user()->id : 'NULL',
            'auth_header' => $request->header('Authorization') ? 'EXISTS' : 'MISSING'
        ]);
        
        $champions = Champion::with(['skins', 'abilities'])->get();

            $champion->load([
                'abilities',
                'skins',
                'rework.abilities',
                'rework.comments.user'
            ]);

            // Προσθήκη unlock status αν ο user είναι logged in
            if ($request->user()) {
                $user = $request->user();

                $unlockedChampionIds = $user->getUnlockedChampionIds();
                $unlockedSkinIds = $user->getUnlockedSkinIds();
                // Champion unlock status
                $$unlockedChampionIds = $user->getUnlockedChampionIds();
                $champion->user_has_unlocked = $champion->is_unlocked_by_default ||
                    in_array($champion->id, $unlockedChampionIds);

                // Skins unlock status
                if ($champion->skins) {
                    $unlockedSkinIds = $user->getUnlockedSkinIds();
                    $champion->skins->each(function ($skin) use ($user, $unlockedSkinIds) {
                        $skin->user_has_unlocked = $skin->is_unlocked_by_default ||
                            in_array($skin->id, $unlockedSkinIds);
                        $skin->user_can_unlock = $user->canUnlock($skin);
                    });
                }
            } else {
                // Αν δεν είναι logged in
                $champion->user_has_unlocked = $champion->is_unlocked_by_default;
                $champion->user_can_unlock = false;

                if ($champion->skins) {
                    $champion->skins->each(function ($skin) {
                        $skin->user_has_unlocked = $skin->is_unlocked_by_default;
                        $skin->user_can_unlock = false;
                    });
                }
            }

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
     * Get champions by role with unlock status
     */
    public function getChampionsByRole(Request $request, string $role): JsonResponse
    {
        try {
            $champions = Champion::where('role', $role)
                ->with(['skins', 'abilities'])
                ->get();

            // Προσθήκη unlock status
            if ($request->user()) {
                $user = $request->user();
                $unlockedChampionIds = $user->getUnlockedChampionIds();
                $unlockedSkinIds = $user->getUnlockedSkinIds();

                $champions->each(function ($champion) use ($user, $unlockedChampionIds, $unlockedSkinIds) {
                    $champion->user_has_unlocked = $champion->is_unlocked_by_default ||
                        in_array($champion->id, $unlockedChampionIds);
                    $champion->user_can_unlock = $user->canUnlock($champion);

                    if ($champion->skins) {
                        $champion->skins->each(function ($skin) use ($user, $unlockedSkinIds) {
                            $skin->user_has_unlocked = $skin->is_unlocked_by_default ||
                                in_array($skin->id, $unlockedSkinIds);
                            $skin->user_can_unlock = $user->canUnlock($skin);
                        });
                    }
                });
            } else {
                $champions->each(function ($champion) {
                    $champion->user_has_unlocked = $champion->is_unlocked_by_default;
                    $champion->user_can_unlock = false;

                    if ($champion->skins) {
                        $champion->skins->each(function ($skin) {
                            $skin->user_has_unlocked = $skin->is_unlocked_by_default;
                            $skin->user_can_unlock = false;
                        });
                    }
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

    /**
     * Search champions with unlock status
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');

            $champions = Champion::where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('title', 'like', "%{$query}%")
                    ->orWhere('role', 'like', "%{$query}%")
                    ->orWhere('region', 'like', "%{$query}%");
            })
                ->with(['skins', 'abilities'])
                ->get();

            // Προσθήκη unlock status
            if ($request->user()) {
                $user = $request->user();
                $unlockedChampionIds = $user->getUnlockedChampionIds();
                $unlockedSkinIds = $user->getUnlockedSkinIds();

                $champions->each(function ($champion) use ($user, $unlockedChampionIds, $unlockedSkinIds) {
                    $champion->user_has_unlocked = $champion->is_unlocked_by_default ||
                        in_array($champion->id, $unlockedChampionIds);
                    $champion->user_can_unlock = $user->canUnlock($champion);

                    if ($champion->skins) {
                        $champion->skins->each(function ($skin) use ($user, $unlockedSkinIds) {
                            $skin->user_has_unlocked = $skin->is_unlocked_by_default ||
                                in_array($skin->id, $unlockedSkinIds);
                            $skin->user_can_unlock = $user->canUnlock($skin);
                        });
                    }
                });
            } else {
                $champions->each(function ($champion) {
                    $champion->user_has_unlocked = $champion->is_unlocked_by_default;
                    $champion->user_can_unlock = false;

                    if ($champion->skins) {
                        $champion->skins->each(function ($skin) {
                            $skin->user_has_unlocked = $skin->is_unlocked_by_default;
                            $skin->user_can_unlock = false;
                        });
                    }
                });
            }

            return response()->json([
                'success' => true,
                'data' => $champions,
                'message' => "Search results for '{$query}'"
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in ChampionController@search: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to search champions',
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