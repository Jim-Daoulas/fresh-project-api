<?php

use Illuminate\Support\Facades\Route;

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