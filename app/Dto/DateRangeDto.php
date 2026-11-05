<?php

namespace App\Dto;

use Carbon\CarbonImmutable;

/** Inclusive range of local dates (Y-m-d). */
final readonly class DateRangeDto
{
    public function __construct(public string $from, public string $to) {}

    /** Missing `to` = today, missing `from` = `defaultDays` days back. Ranges are capped at `maxDays`. */
    public static function fromArray(array $data, string $today, int $defaultDays, int $maxDays = 3660): self
    {
        $to = $data['to'] ?? $today;
        $from = $data['from'] ?? CarbonImmutable::parse($to)->subDays($defaultDays - 1)->toDateString();
        $min = CarbonImmutable::parse($to)->subDays($maxDays)->toDateString();

        return new self(max($from, $min), $to);
    }
}
