<?php

namespace App\Dto;

/**
 * Partial change of a saved product by id. null = "not changed" and is filtered out, as in MealUpdateDto.
 */
final readonly class ProductUpdateDto
{
    private const FIELDS = ['name', 'kcal_100g', 'protein_100g', 'fat_100g', 'carbs_100g', 'default_portion_g', 'piece_g', 'notes'];

    /** @param array<string, mixed> $attributes */
    public function __construct(public array $attributes) {}

    public static function fromArray(array $data): self
    {
        $attributes = array_filter(array_intersect_key($data, array_flip(self::FIELDS)), fn ($v) => $v !== null);
        foreach (['name', 'notes'] as $text) {
            if (isset($attributes[$text])) {
                $attributes[$text] = trim((string) $attributes[$text]);
            }
        }

        return new self($attributes);
    }
}
