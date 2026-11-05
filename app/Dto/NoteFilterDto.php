<?php

namespace App\Dto;

use App\Enums\NoteKind;

final readonly class NoteFilterDto
{
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
        public ?NoteKind $kind = null,
        public int $limit = 200,
    ) {}

    public static function fromArray(array $data, int $limit = 200): self
    {
        return new self($data['from'] ?? null, $data['to'] ?? null, isset($data['kind']) ? NoteKind::from($data['kind']) : null, $limit);
    }
}
