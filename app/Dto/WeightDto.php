<?php

namespace App\Dto;

use Carbon\CarbonImmutable;

final readonly class WeightDto
{
    public function __construct(
        public float $weightKg,
        public CarbonImmutable $measuredAt,
        public ?string $notes,
    ) {}

    public static function fromArray(array $data, string $timezone): self
    {
        return new self((float) $data['weight_kg'], Time::toUtc($data['measured_at'] ?? null, $timezone), $data['notes'] ?? null);
    }

    public function toAttributes(): array
    {
        return ['weight_kg' => $this->weightKg, 'measured_at' => $this->measuredAt, 'notes' => $this->notes];
    }
}
