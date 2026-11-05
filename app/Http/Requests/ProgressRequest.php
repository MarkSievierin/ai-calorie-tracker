<?php

namespace App\Http\Requests;

class ProgressRequest extends ApiRequest
{
    public static function fieldRules(): array
    {
        return ['days' => ['sometimes', 'nullable', 'integer', 'between:7,365']];
    }

    public function days(): int
    {
        return (int) ($this->validated()['days'] ?? 28);
    }
}
