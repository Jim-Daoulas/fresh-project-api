<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'hello World']);
});

Route::get('/test', function () {
    return response()->json(['message' => 'Test route works!']);
});
//Route::prefix('admin')->name('admin')->group(base_path('routes/admin.php'));
Route::prefix('users')->name('users')->group(base_path('routes/users.php'));
Route::prefix('champions')->name('champions')->group(base_path('routes/champions.php'));
