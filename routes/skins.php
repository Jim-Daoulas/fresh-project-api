<?php

use App\Http\Controllers\ChampionController;
use App\Http\Controllers\ReworkController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\SkinController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ChampionController::class, 'index']);
Route::get('/champions', [ChampionController::class, 'index']);
Route::get('/{champion}', [ChampionController::class, 'show']);

// Skin routes (public)
Route::get('/{champion}/skins', [SkinController::class, 'getChampionSkins']);
Route::get('/skins/{skin}', [SkinController::class, 'show']);

// Protected routes - Για τα unlocks και σχόλια (απαιτούν αυθεντικοποίηση)
Route::middleware(['auth:sanctum'])->group(function() {
    // Unlock champion endpoint
    Route::post('/{champion}/unlock', [ChampionController::class, 'unlock']);
    
    // Skin unlock endpoint
    Route::post('/skins/{skin}/unlock', [SkinController::class, 'unlock']);
    
    // Λήψη σχολίων για το rework ενός champion
    Route::get('/{champion}/rework/comments', [CommentController::class, 'getChampionReworkComments']);
    
    // Προσθήκη σχολίου στο rework ενός champion
    Route::post('/{champion}/rework/comments', [CommentController::class, 'addCommentToChampionRework']);
});