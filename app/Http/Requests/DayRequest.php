<?php

namespace App\Http\Requests;

class DayRequest extends ApiRequest
{
    public static function fieldRules(): array
    {
        return ['date' => ['sometimes', 'nullable', 'date_format:Y-m-d']];
    }

    public function day(): ?string
    {
        return $this->validated()['date'] ?? null;
    }
}
