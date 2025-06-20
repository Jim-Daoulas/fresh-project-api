<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        apiPrefix:'',
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
        \App\Http\Middleware\CorsMiddleware::class,
    ]);
    $middleware->alias([
        'setAuthRole' => \App\Http\Middleware\SetAuthRole::class,
        'checkRole' => \App\Http\Middleware\CheckRole::class,
    ]);
    
    // Πρόσθεσε αυτό για να διορθώσεις το CSRF
    $middleware->validateCsrfTokens(except: [
        'admin/login',
        'admin/*'
    ]);
    $middleware->trustProxies(at: '*');
})
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
