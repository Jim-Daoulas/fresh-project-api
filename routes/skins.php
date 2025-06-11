<?php

use App\Http\Controllers\SkinController;
use Illuminate\Support\Facades\Route;

// ✅ PUBLIC: For guests - shows only default unlocked skins
Route::get('/champion/{championId}/public', [SkinController::class, 'getPublicSkinsForChampion']);

// Other public routes if needed
Route::get('/search', [SkinController::class, 'search']); // για μελλοντική χρήση

// ✅ PRIVATE: For authenticated users
Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/champion/{championId}', [SkinController::class, 'getSkinsForChampion']);
    Route::post('/{skinId}/unlock', [SkinController::class, 'unlock']);
    Route::get('/my-skins', [SkinController::class, 'getUserUnlockedSkins']);
    Route::get('/unlocked', [SkinController::class, 'getUserUnlockedSkins']); // alias
});