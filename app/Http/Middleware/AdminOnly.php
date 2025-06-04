<?php

namespace App\Http\Middleware;

use App\Enum\RoleCode;
use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->user()->roles()->where('role_id', RoleCode::admin)->exists()) {
            abort(403, 'Admin access required');
        }
        return $next($request);
    }
}