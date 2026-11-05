<?php

namespace App\Http\Requests;

use App\Dto\WeightDto;

class StoreWeightRequest extends ApiRequest
{
    public static function fieldRules(): array
    {
        return [
            'weight_kg' => ['required', 'numeric', 'between:30,300'],
            'measured_at' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function toDto(): WeightDto
    {
        return WeightDto::fromArray($this->validated(), $this->settings()->timezone());
    }
}
