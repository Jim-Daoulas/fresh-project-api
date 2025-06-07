<?php

use App\Http\Controllers\ChampionController;
use App\Http\Controllers\ReworkController;
use App\Http\Controllers\CommentController;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)
Route::get('/', [ChampionController::class, 'index']);
Route::get('/champions', [ChampionController::class, 'index']);
Route::get('/test', [ChampionController::class, 'test']);
Route::get('/search', [ChampionController::class, 'search']);
Route::get('/role/{role}', [ChampionController::class, 'getChampionsByRole']);
Route::get('/{champion}', [ChampionController::class, 'show']);

// Protected routes - Require authentication
Route::middleware(['auth:sanctum'])->group(function() {
    // Champion unlock status and unlock actions are handled in user routes
    Route::get('/{champion}/unlock-status', [ChampionController::class, 'getUnlockStatus']);
    
    // Rework comments
    Route::get('/{champion}/rework/comments', [CommentController::class, 'getChampionReworkComments']);
    Route::post('/{champion}/rework/comments', [CommentController::class, 'addCommentToChampionRework']);
    
    // General comment CRUD
    Route::apiResource('comments', CommentController::class)->only(['store', 'update', 'destroy']);
});

// Public comment viewing
Route::get('/comments', [CommentController::class, 'index']);
Route::get('/comments/{comment}', [CommentController::class, 'show']);