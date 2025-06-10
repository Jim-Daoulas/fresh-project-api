<?php

use App\Http\Controllers\ChampionController;
use App\Http\Controllers\ReworkController;
use App\Http\Controllers\CommentController;
use Illuminate\Support\Facades\Route;

// ✅ PUBLIC: For guests - shows default unlocked champions
Route::get('/', [ChampionController::class, 'publicIndex']);
Route::get('/public', [ChampionController::class, 'publicIndex']);

// ✅ PRIVATE: For authenticated users - shows personalized unlock status
Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/champions', [ChampionController::class, 'index']);
    Route::get('/my-champions', [ChampionController::class, 'index']);
});

// Other routes remain the same...
Route::get('/{champion}', [ChampionController::class, 'show']);
Route::get('/role/{role}', [ChampionController::class, 'getChampionsByRole']);
Route::get('/search', [ChampionController::class, 'search']);

Route::middleware(['auth:sanctum'])->group(function() {
    Route::post('/{champion}/unlock', [ChampionController::class, 'unlock']);
    Route::get('/{champion}/rework/comments', [CommentController::class, 'getChampionReworkComments']);
    Route::post('/{champion}/rework/comments', [CommentController::class, 'addCommentToChampionRework']);
});