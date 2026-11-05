<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Single static token for everything. Accepted from:
 * - ?key=<token>                 (claude.ai connector form takes only a URL, so the secret rides in it)
 * - Authorization: Bearer <token> (curl, scripts)
 * - kkal_token cookie            (web app after login)
 */
class RequireToken
{
    public const COOKIE = 'kkal_token';

    public function handle(Request $request, Closure $next): Response
    {
        $limiterKey = 'auth-fail:'.$request->ip();
        $limit = (int) config('kkal.auth_fail_limit');

        if (RateLimiter::tooManyAttempts($limiterKey, $limit)) {
            return response()->json(['error' => 'too_many_attempts'], 429);
        }

        if (! self::valid(self::presented($request))) {
            RateLimiter::hit($limiterKey, 60);

            return response()->json(['error' => 'unauthorized'], 401);
        }

        return $next($request);
    }

    public static function presented(Request $request): ?string
    {
        $value = $request->query('key') ?? $request->bearerToken() ?? $request->cookie(self::COOKIE);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function valid(?string $presented): bool
    {
        $expected = (string) config('kkal.token');

        // No configured token = everything is closed. hash_equals compares in constant time.
        return $expected !== '' && $presented !== null && hash_equals($expected, $presented);
    }
}
