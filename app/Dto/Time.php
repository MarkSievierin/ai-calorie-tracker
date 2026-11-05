<?php

namespace App\Dto;

use Carbon\CarbonImmutable;

final class Time
{
    /** ISO 8601 input; without an offset it is read in the user's timezone. Null = now. */
    public static function toUtc(?string $value, string $timezone): CarbonImmutable
    {
        return ($value ? CarbonImmutable::parse($value, $timezone) : CarbonImmutable::now())->utc();
    }
}
