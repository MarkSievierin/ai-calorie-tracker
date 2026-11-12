<?php

namespace App\Dto;

/**
 * One component of a meal.
 * Estimated item: the agent gives name and kcal (macros and kcal_max per item only when the meal is summed
 * from items, see MealItemsService). Product item: product_id + grams or pieces; the server fills the numbers.
 */
final readonly class MealItemDto
{
    public function __construct(
        public ?string $name,
        public ?float $grams,
        public ?float $kcal,
        public ?int $productId = null,
        public ?float $pieces = null,
        public ?float $kcalMax = null,
        public ?float $proteinG = null,
        public ?float $fatG = null,
        public ?float $carbsG = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $f = fn (string $k) => isset($data[$k]) ? (float) $data[$k] : null;

        return new self(
            name: isset($data['name']) ? (string) $data['name'] : null,
            grams: $f('grams'),
            kcal: $f('kcal'),
            productId: isset($data['product_id']) ? (int) $data['product_id'] : null,
            pieces: $f('pieces'),
            kcalMax: $f('kcal_max'),
            proteinG: $f('protein_g'),
            fatG: $f('fat_g'),
            carbsG: $f('carbs_g'),
        );
    }

    public function isProduct(): bool
    {
        return $this->productId !== null;
    }

    /** Stored shape: name, grams, kcal always; the rest only when known, so old records keep their shape. */
    public function toArray(): array
    {
        return ['name' => $this->name, 'grams' => $this->grams, 'kcal' => $this->kcal] + array_filter([
            'product_id' => $this->productId,
            'pieces' => $this->pieces,
            'kcal_max' => $this->kcalMax,
            'protein_g' => $this->proteinG,
            'fat_g' => $this->fatG,
            'carbs_g' => $this->carbsG,
        ], fn ($v) => $v !== null);
    }
}
