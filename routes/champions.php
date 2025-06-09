<?php

use App\Http\Controllers\ChampionController;
use App\Http\Controllers\ReworkController;
use App\Http\Controllers\CommentController;
use Illuminate\Support\Facades\Route;

// ✅ PUBLIC ROUTES (χωρίς auth:sanctum):
Route::get('/', [ChampionController::class, 'index']);
Route::get('/champions', [ChampionController::class, 'index']);
Route::get('/{champion}', [ChampionController::class, 'show']);
Route::get('/role/{role}', [ChampionController::class, 'getChampionsByRole']);
Route::get('/search', [ChampionController::class, 'search']);

// Protected routes - Μόνο για comments
Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/{champion}/rework/comments', [CommentController::class, 'getChampionReworkComments']);
    Route::post('/{champion}/rework/comments', [CommentController::class, 'addCommentToChampionRework']);
});