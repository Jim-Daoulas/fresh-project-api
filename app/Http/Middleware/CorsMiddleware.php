<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Debug log
        \Log::info('CORS Middleware hit', [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'origin' => $request->header('Origin')
        ]);

        // Handle preflight requests
        if ($request->getMethod() === "OPTIONS") {
            \Log::info('Handling OPTIONS request');
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        }

        $response = $next($request);
        
        // Add CORS headers to all responses
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        
        \Log::info('CORS headers added to response');
        
        return $response;
    }
}