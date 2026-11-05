<?php

namespace App\Enums;

/** Where a meal estimate came from. */
enum MealSource: string
{
    case Photo = 'photo';
    case Text = 'text';
    case Label = 'label';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
