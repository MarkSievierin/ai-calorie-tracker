<?php

namespace App\Http\Requests;

class LoginRequest extends ApiRequest
{
    public static function fieldRules(): array
    {
        return ['token' => ['required', 'string', 'max:200']];
    }

    public function token(): string
    {
        return (string) $this->validated()['token'];
    }
}
