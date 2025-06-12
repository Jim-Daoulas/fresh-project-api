<?php

use App\Http\Controllers\SkinController;
use Illuminate\Support\Facades\Route;

// ✅ PUBLIC ROUTES - Για guests
Route::get('/public/champion/{championId}', [SkinController::class, 'getPublicSkinsForChampion']);
Route::get('/public/{id}', [SkinController::class, 'show']);

// ✅ AUTHENTICATED ROUTES - Για logged-in users
Route::middleware(['auth:sanctum'])->group(function() {
    // Get skins for a specific champion (authenticated)
    Route::get('/champion/{championId}', [SkinController::class, 'getSkinsForChampion']);
    
    // Get specific skin details
    Route::get('/{id}', [SkinController::class, 'show']);
    
    // Unlock a skin
    Route::post('/{skinId}/unlock', [SkinController::class, 'unlock']);
    
    // Get user's unlocked skins
    Route::get('/user/unlocked', [SkinController::class, 'getUserUnlockedSkins']);
    
    // Admin/Debug: Get all skins
    Route::get('/', [SkinController::class, 'index']);
});