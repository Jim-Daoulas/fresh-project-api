<?php

use App\Http\Controllers\UnlockController;
use Illuminate\Support\Facades\Route;

// Unlock system routes - απαιτούν authentication
Route::middleware(['auth:sanctum'])->group(function() {
    
    // User progression info
    Route::get('/progress', [UnlockController::class, 'getUserProgress']);
    
    // Unlock actions - ✅ Αφαίρεσα το /unlock prefix
    Route::post('/champion/{champion}', [UnlockController::class, 'unlockChampion']);
    Route::post('/skin/{skin}', [UnlockController::class, 'unlockSkin']);
    
    // Get available unlocks
    Route::get('/available-unlocks', [UnlockController::class, 'getAvailableUnlocks']);
    Route::get('/locked-items', [UnlockController::class, 'getLockedItems']);
    
    // Add points (για testing - μπορείς να το αφαιρέσεις αργότερα)
    Route::post('/add-points', [UnlockController::class, 'addPoints']);
});