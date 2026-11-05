<?php

namespace App\Dto;

/** Nutrition per 100 g from a label. */
final readonly class ProductDto
{
    public function __construct(
        public string $name,
        public float $kcal100g,
        public float $protein100g,
        public float $fat100g,
        public float $carbs100g,
        public ?float $defaultPortionG,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['name'],
            (float) $data['kcal_100g'],
            (float) $data['protein_100g'],
            (float) $data['fat_100g'],
            (float) $data['carbs_100g'],
            isset($data['default_portion_g']) ? (float) $data['default_portion_g'] : null,
        );
    }

    public function toAttributes(): array
    {
        return [
            'kcal_100g' => $this->kcal100g,
            'protein_100g' => $this->protein100g,
            'fat_100g' => $this->fat100g,
            'carbs_100g' => $this->carbs100g,
            'default_portion_g' => $this->defaultPortionG,
        ];
    }
}
