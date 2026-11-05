<?php

namespace App\Enums;

enum NoteKind: string
{
    case Observation = 'observation';
    case Suggestion = 'suggestion';
    case Question = 'question';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
