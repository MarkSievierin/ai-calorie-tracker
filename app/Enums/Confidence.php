<?php

namespace App\Enums;

/** How sure the estimate of a meal is. */
enum Confidence: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
