<?php

namespace App\Http\Controllers\Api;

use App\Http\Middleware\RequireToken;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;

class AuthController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $token = $request->token();
        if (! RequireToken::valid($token)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        // HttpOnly + SameSite=Strict: not readable by JS and not sent on cross-site requests.
        return response()->json(['ok' => true])->withCookie(
            Cookie::make(RequireToken::COOKIE, $token, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'strict'),
        );
    }

    public function logout(): JsonResponse
    {
        return response()->json(['ok' => true])->withCookie(Cookie::forget(RequireToken::COOKIE));
    }
}
