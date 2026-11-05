<?php

namespace App\Http\Requests;

use App\Dto\DateRangeDto;

class DateRangeRequest extends ApiRequest
{
    public static function fieldRules(): array
    {
        return [
            'from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'types' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function toDto(int $defaultDays = 28): DateRangeDto
    {
        return DateRangeDto::fromArray($this->validated(), $this->settings()->today(), $defaultDays);
    }

    /** @return list<string>|null comma-separated ?types= */
    public function types(): ?array
    {
        $types = $this->validated()['types'] ?? null;

        return $types ? array_values(array_filter(explode(',', $types))) : null;
    }
}
