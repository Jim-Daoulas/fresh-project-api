<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
{
    // Χρησιμοποιήστε updateOrCreate αντί για create
    User::updateOrCreate(
        ['email' => 'admin@example.com'],
        [
            'name' => 'Admin User',
            'password' => Hash::make('password'),
        ]
    );

    User::updateOrCreate(
        ['email' => 'user@example.com'],
        [
            'name' => 'Test User', 
            'password' => Hash::make('password'),
        ]
    );
}
}