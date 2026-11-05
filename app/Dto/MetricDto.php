<?php

namespace App\Dto;

final readonly class MetricDto
{
    public function __construct(
        public string $type,
        public float $value,
        public string $date,
        public ?string $notes,
    ) {}

    public static function fromArray(array $data, string $today): self
    {
        return new self((string) $data['type'], (float) $data['value'], $data['date'] ?? $today, $data['notes'] ?? null);
    }

    public function toAttributes(): array
    {
        return ['type' => $this->type, 'value' => $this->value, 'date' => $this->date, 'notes' => $this->notes];
    }
}
