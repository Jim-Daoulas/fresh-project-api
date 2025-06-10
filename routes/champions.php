<?php

use App\Http\Controllers\ChampionController;
use App\Http\Controllers\ReworkController;
use App\Http\Controllers\CommentController;
use Illuminate\Support\Facades\Route;

// ✅ CHANGE: Add auth middleware to index route
Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/', [ChampionController::class, 'index']);
    Route::get('/champions', [ChampionController::class, 'index']);
});

// Public routes (no auth needed)
Route::get('/{champion}', [ChampionController::class, 'show']);
Route::get('/role/{role}', [ChampionController::class, 'getChampionsByRole']);
Route::get('/search', [ChampionController::class, 'search']);

// Protected routes - Για τα unlocks και σχόλια
Route::middleware(['auth:sanctum'])->group(function() {
    Route::post('/{champion}/unlock', [ChampionController::class, 'unlock']);
    Route::get('/{champion}/rework/comments', [CommentController::class, 'getChampionReworkComments']);
    Route::post('/{champion}/rework/comments', [CommentController::class, 'addCommentToChampionRework']);
});