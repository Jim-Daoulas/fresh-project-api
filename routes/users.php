<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'hello user']);
});

Route::prefix('auth')->middleware("setAuthRole:2")->group(base_path('routes/auth.php'));

Route::prefix("user")
    ->middleware('auth:sanctum')
    ->group(function() {
        Route::get("me", [UserController::class, 'me']);
        Route::get("tokens", [UserController::class, 'tokens']);
        Route::delete("revoke-all-tokens", [UserController::class, 'revokeAllTokens']);
        
        // Progression routes
        Route::get("progress", [UserController::class, 'getProgress']);
        Route::post("daily-bonus", [UserController::class, 'claimDailyBonus']);
        Route::get("unlocks", [UserController::class, 'getAvailableUnlocks']);
        Route::post("unlock/champion/{championId}", [UserController::class, 'unlockChampion']);
        Route::post("unlock/skin/{skinId}", [UserController::class, 'unlockSkin']);
        Route::post("track/champion-view", [UserController::class, 'trackChampionView']);
        Route::post("track/comment", [UserController::class, 'trackComment']);
    });