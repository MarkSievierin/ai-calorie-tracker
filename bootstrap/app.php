<?php

use App\Http\Middleware\RequireToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // nginx on the host is the only proxy; it sets X-Forwarded-* for the real client IP.
        $middleware->trustProxies(at: '*');
        $middleware->alias(['token' => RequireToken::class]);
        // The auth cookie holds the raw token and is read outside the web group.
        $middleware->encryptCookies(except: [RequireToken::COOKIE]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*', 'mcp') || $request->expectsJson(),
        );
    })->create();
