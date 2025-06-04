<?php

namespace App\Http\Middleware;

use App\Enum\RoleCode;
use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user) {
            // Debug: User not authenticated
            abort(403, 'User not authenticated');
        }
        
        $userRoles = $user->roles()->pluck('role_id')->toArray();
        $hasAdminRole = $user->roles()->where('role_id', RoleCode::admin)->exists();
        
        // Debug info - θα το δείτε στο error page
        if (!$hasAdminRole) {
            abort(403, 'Admin access required. User roles: ' . implode(',', $userRoles) . ' | Looking for role: ' . RoleCode::admin);
        }
        
        return $next($request);
    }
}