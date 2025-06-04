<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'hello user']);
});

Route::prefix('auth')->middleware("setAuthRole:2")->group(base_path('routes/auth.php'));

Route::prefix("user")
    ->middleware('auth:sanctum')
    ->group(function() {
        Route::get("me", [UserController::class, 'me']);
        Route::get("tokens", [UserController::class, 'tokens']);
        Route::delete("revoke-all-tokens", [UserController::class, 'revokeAllTokens']);
    });