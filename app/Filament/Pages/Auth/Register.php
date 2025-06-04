<?php

namespace App\Filament\Pages\Auth;

use App\Models\Role;
use App\Enum\RoleCode;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    protected function handleRegistration(array $data): Model
    {
        // Δημιουργία χρήστη χωρίς το boot method interference
        $user = $this->getUserModel()::create($data);
        
        // Μανουαλή εκχώρηση admin role
        $adminRole = Role::find(RoleCode::admin);
        if ($adminRole && !$user->roles()->where('role_id', $adminRole->id)->exists()) {
            $user->roles()->attach($adminRole->id);
        }
        
        return $user;
    }
}