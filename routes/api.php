<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return response()->json(['message' => 'hello World']);
});

Route::get('/test', function () {
    return response()->json(['message' => 'Test route works!']);
});
//Route::prefix('admin')->name('admin')->group(base_path('routes/admin.php'));
Route::prefix('users')->name('users')->group(base_path('routes/users.php'));
Route::prefix('champions')->name('champions')->group(base_path('routes/champions.php'));
Route::prefix('unlocks')->name('unlocks')->group(base_path('routes/unlocks.php'));

Route::get('/debug-unlock/{userId}', function($userId) {
    try {
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found']);
        }

        $unlocks = \App\Models\UserUnlock::where('user_id', $userId)->get();
        $championIds = $user->getUnlockedChampionIds();
        
        return response()->json([
            'user_id' => $userId,
            'user_points' => $user->points,
            'unlocks_in_db' => $unlocks->toArray(),
            'unlocked_champion_ids' => $championIds,
            'total_unlocks' => $unlocks->count()
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});
Route::get('/test-champions', function(Request $request) {
    $user = $request->user();
    if (!$user) {
        return response()->json(['error' => 'Not authenticated']);
    }
    
    $unlockedIds = $user->getUnlockedChampionIds();
    $champion4 = \App\Models\Champion::find(4);
    $champion5 = \App\Models\Champion::find(5);
    
    return response()->json([
        'user_id' => $user->id,
        'unlocked_ids' => $unlockedIds,
        'champion_4' => [
            'id' => 4,
            'name' => $champion4->name,
            'is_unlocked_by_default' => $champion4->is_unlocked_by_default,
            'should_be_unlocked' => in_array(4, $unlockedIds)
        ],
        'champion_5' => [
            'id' => 5,  
            'name' => $champion5->name,
            'is_unlocked_by_default' => $champion5->is_unlocked_by_default,
            'should_be_unlocked' => in_array(5, $unlockedIds)
        ]
    ]);
})->middleware('auth:sanctum');