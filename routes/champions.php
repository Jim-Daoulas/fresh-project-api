<?php

use App\Http\Controllers\ChampionController;
use App\Http\Controllers\ReworkController;
use App\Http\Controllers\CommentController;
use Illuminate\Support\Facades\Route;

// ✅ PUBLIC: For guests - shows default unlocked champions
Route::get('/', [ChampionController::class, 'publicIndex']);
Route::get('/public', [ChampionController::class, 'publicIndex']);

// ✅ PUBLIC: Champion details for guests (only default unlocked)
Route::get('/public/{champion}', [ChampionController::class, 'showPublic']);

// Other public routes
Route::get('/role/{role}', [ChampionController::class, 'getChampionsByRole']);
Route::get('/search', [ChampionController::class, 'search']);

// ✅ PRIVATE: For authenticated users
Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/champions', [ChampionController::class, 'index']);
    Route::get('/my-champions', [ChampionController::class, 'index']);
    Route::get('/{champion}', [ChampionController::class, 'show']); // Για logged users
    Route::post('/{champion}/unlock', [ChampionController::class, 'unlock']);
    Route::get('/{champion}/rework/comments', [CommentController::class, 'getChampionReworkComments']);
    Route::post('/{champion}/rework/comments', [CommentController::class, 'addCommentToChampionRework']);
});