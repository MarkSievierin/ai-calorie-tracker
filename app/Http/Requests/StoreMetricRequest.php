<?php

namespace App\Http\Requests;

use App\Dto\MetricDto;

class StoreMetricRequest extends ApiRequest
{
    public static function fieldRules(): array
    {
        return [
            // Open set of types (steps, sleep_h, bp_sys, ...): a new metric needs no migration or enum case.
            'type' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,39}$/'],
            'value' => ['required', 'numeric', 'between:-1000000,10000000'],
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function toDto(): MetricDto
    {
        return MetricDto::fromArray($this->validated(), $this->settings()->today());
    }
}
