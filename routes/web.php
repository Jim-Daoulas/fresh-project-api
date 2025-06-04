<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Προσθήκη για Filament authentication
Route::post('/admin/login', function() {
    // Αυτό θα το χειριστεί το Filament αυτόματα
})->name('filament.admin.auth.login');