<?php

namespace App\Http\Middleware;

use App\Enum\RoleCode;
use Closure;
use Illuminate\Http\Request;

class FilamentAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user || !$user->roles()->where('role_id', RoleCode::admin)->exists()) {
            abort(403, 'Access denied. Admin role required.');
        }

        return $next($request);
    }
}