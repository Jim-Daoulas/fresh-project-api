<?php

use App\Http\Controllers\ChampionController;
use App\Http\Controllers\ReworkController;
use App\Http\Controllers\CommentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ChampionController::class, 'index']);

Route::get('/champions', [ChampionController::class, 'index']);
Route::get('/{champion}', [ChampionController::class, 'show']);
Route::get('/role/{role}', [ChampionController::class, 'getChampionsByRole']);
Route::get('/search', [ChampionController::class, 'search']);

// Protected routes - Για τα σχόλια (απαιτούν αυθεντικοποίηση)
Route::middleware(['auth:sanctum'])->group(function() {
    // Λήψη σχολίων για το rework ενός champion
    Route::get('/{champion}/rework/comments', [CommentController::class, 'getChampionReworkComments']);
    
    // Προσθήκη σχολίου στο rework ενός champion
    Route::post('/{champion}/rework/comments', [CommentController::class, 'addCommentToChampionRework']);
});