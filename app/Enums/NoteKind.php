<?php

namespace App\Enums;

enum NoteKind: string
{
    case Observation = 'observation';
    case Suggestion = 'suggestion';
    case Question = 'question';
    /** A standing fact or preference of the user. Always sent to the agent, unlike dated findings. */
    case Rule = 'rule';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
