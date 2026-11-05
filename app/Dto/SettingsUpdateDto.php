<?php

namespace App\Dto;

/**
 * Settings to change. Unlike entity updates, null here is a real value ("not set", e.g. no target weight),
 * so the DTO keeps exactly the keys that were sent.
 */
final readonly class SettingsUpdateDto
{
    /** @param array<string, mixed> $values */
    public function __construct(public array $values) {}
}
