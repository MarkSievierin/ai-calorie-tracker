<?php

namespace App\Dto;

final readonly class MealItemDto
{
    public function __construct(
        public string $name,
        public ?float $grams,
        public float $kcal,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self((string) $data['name'], isset($data['grams']) ? (float) $data['grams'] : null, (float) $data['kcal']);
    }

    public function toArray(): array
    {
        return ['name' => $this->name, 'grams' => $this->grams, 'kcal' => $this->kcal];
    }
}
