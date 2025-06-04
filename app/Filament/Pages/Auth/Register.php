<?php

namespace App\Filament\Pages\Auth;

use App\Models\Role;
use App\Enum\RoleCode;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Register extends BaseRegister
{
    protected function handleRegistration(array $data): Model
    {
        // Δημιουργία χρήστη με τον default τρόπο
        $user = $this->getUserModel()::create($data);
        
        // Προσθήκη admin role
        $adminRole = Role::find(RoleCode::admin);
        if ($adminRole && !$user->roles()->where('role_id', $adminRole->id)->exists()) {
            $user->roles()->attach($adminRole->id);
        }
        
        // ΣΗΜΑΝΤΙΚΟ: Login του χρήστη μετά τη δημιουργία
        Auth::login($user);
        
        return $user;
    }
}