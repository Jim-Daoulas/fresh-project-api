<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return response()->json(['message' => 'hello World']);
});

Route::get('/test', function () {
    return response()->json(['message' => 'Test route works!']);
});
//Route::prefix('admin')->name('admin')->group(base_path('routes/admin.php'));
Route::prefix('users')->name('users')->group(base_path('routes/users.php'));
Route::prefix('champions')->name('champions')->group(base_path('routes/champions.php'));
Route::prefix('unlocks')->name('unlocks')->group(base_path('routes/unlocks.php'));
Route::prefix('skins')->name('skins')->group(base_path('routes/skins.php'));
