<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Single static token for everything. Accepted from:
 * - Authorization: Bearer <token> (MCP clients with request headers, e.g. claude.ai custom connector; curl)
 * - X-Api-Key: <token>            (clients that only let you set a plain header)
 * - kkal_token cookie             (web app after login)
 * - ?key=<token>                  (fallback for clients that take only a URL; the secret then lives in the URL)
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
        $value = $request->bearerToken()
            ?? $request->header('X-Api-Key')
            ?? $request->cookie(self::COOKIE)
            ?? $request->query('key');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function valid(?string $presented): bool
    {
        $expected = (string) config('kkal.token');

        // No configured token = everything is closed. hash_equals compares in constant time.
        return $expected !== '' && $presented !== null && hash_equals($expected, $presented);
    }
}
