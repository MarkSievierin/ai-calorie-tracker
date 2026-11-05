<?php

namespace App\Dto;

use App\Enums\Confidence;
use App\Enums\MealSource;
use Carbon\CarbonImmutable;

/**
 * Partial meal update: null = "not changed". A field cannot be cleared to null through an update.
 */
final readonly class MealUpdateDto
{
    /** @param list<MealItemDto>|null $items */
    public function __construct(
        public ?string $title = null,
        public ?array $items = null,
        public ?float $kcalEst = null,
        public ?float $kcalMax = null,
        public ?float $proteinG = null,
        public ?float $fatG = null,
        public ?float $carbsG = null,
        public ?float $sugarG = null,
        public ?float $fiberG = null,
        public ?float $satFatG = null,
        public ?float $saltG = null,
        public ?Confidence $confidence = null,
        public ?MealSource $source = null,
        public ?CarbonImmutable $eatenAt = null,
        public ?string $notes = null,
    ) {}

    /** @param array<string, mixed> $data validated input */
    public static function fromArray(array $data, string $timezone): self
    {
        $f = fn (string $k) => isset($data[$k]) ? (float) $data[$k] : null;

        return new self(
            title: $data['title'] ?? null,
            items: isset($data['items']) ? array_map(MealItemDto::fromArray(...), array_values($data['items'])) : null,
            kcalEst: $f('kcal_est'),
            kcalMax: $f('kcal_max'),
            proteinG: $f('protein_g'),
            fatG: $f('fat_g'),
            carbsG: $f('carbs_g'),
            sugarG: $f('sugar_g'),
            fiberG: $f('fiber_g'),
            satFatG: $f('sat_fat_g'),
            saltG: $f('salt_g'),
            confidence: isset($data['confidence']) ? Confidence::from($data['confidence']) : null,
            source: isset($data['source']) ? MealSource::from($data['source']) : null,
            eatenAt: isset($data['eaten_at']) ? Time::toUtc($data['eaten_at'], $timezone) : null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toAttributes(): array
    {
        return array_filter([
            'title' => $this->title,
            'items' => $this->items === null ? null : array_map(fn (MealItemDto $i) => $i->toArray(), $this->items),
            'kcal_est' => $this->kcalEst,
            'kcal_max' => $this->kcalMax,
            'protein_g' => $this->proteinG,
            'fat_g' => $this->fatG,
            'carbs_g' => $this->carbsG,
            'sugar_g' => $this->sugarG,
            'fiber_g' => $this->fiberG,
            'sat_fat_g' => $this->satFatG,
            'salt_g' => $this->saltG,
            'confidence' => $this->confidence,
            'source' => $this->source,
            'eaten_at' => $this->eatenAt,
            'notes' => $this->notes,
        ], fn ($v) => $v !== null);
    }
}
