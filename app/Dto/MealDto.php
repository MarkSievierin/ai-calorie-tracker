<?php

namespace App\Dto;

use App\Enums\Confidence;
use App\Enums\MealSource;
use Carbon\CarbonImmutable;

/**
 * A new meal. Times are UTC.
 * Totals are null only on input when items use product_id: LogMealAction sums them from items (MealItemsService).
 */
final readonly class MealDto
{
    /** @param list<MealItemDto> $items */
    public function __construct(
        public string $title,
        public array $items,
        public ?float $kcalEst,
        public ?float $kcalMax,
        public ?float $proteinG,
        public ?float $fatG,
        public ?float $carbsG,
        public ?float $sugarG,
        public ?float $fiberG,
        public ?float $satFatG,
        public ?float $saltG,
        public Confidence $confidence,
        public MealSource $source,
        public CarbonImmutable $eatenAt,
        public ?string $notes,
    ) {}

    /** @param array<string, mixed> $data validated input */
    public static function fromArray(array $data, string $timezone): self
    {
        return new self(
            title: (string) $data['title'],
            items: array_map(MealItemDto::fromArray(...), array_values($data['items'] ?? [])),
            kcalEst: self::float($data['kcal_est'] ?? null),
            kcalMax: self::float($data['kcal_max'] ?? null),
            proteinG: self::float($data['protein_g'] ?? null),
            fatG: self::float($data['fat_g'] ?? null),
            carbsG: self::float($data['carbs_g'] ?? null),
            sugarG: self::float($data['sugar_g'] ?? null),
            fiberG: self::float($data['fiber_g'] ?? null),
            satFatG: self::float($data['sat_fat_g'] ?? null),
            saltG: self::float($data['salt_g'] ?? null),
            confidence: Confidence::from($data['confidence']),
            source: MealSource::from($data['source']),
            eatenAt: Time::toUtc($data['eaten_at'] ?? null, $timezone),
            notes: $data['notes'] ?? null,
        );
    }

    /** Totals the client sent, keyed by attribute name. */
    public function sentTotals(): array
    {
        return array_filter([
            'kcal_est' => $this->kcalEst, 'kcal_max' => $this->kcalMax,
            'protein_g' => $this->proteinG, 'fat_g' => $this->fatG, 'carbs_g' => $this->carbsG,
        ], fn ($v) => $v !== null);
    }

    /** @param array{items: list<MealItemDto>, kcal_est: float, kcal_max: float, protein_g: float, fat_g: float, carbs_g: float} $sum */
    public function withSum(array $sum): self
    {
        return new self(
            title: $this->title,
            items: $sum['items'],
            kcalEst: $sum['kcal_est'],
            kcalMax: $sum['kcal_max'],
            proteinG: $sum['protein_g'],
            fatG: $sum['fat_g'],
            carbsG: $sum['carbs_g'],
            sugarG: $this->sugarG,
            fiberG: $this->fiberG,
            satFatG: $this->satFatG,
            saltG: $this->saltG,
            confidence: $this->confidence,
            source: $this->source,
            eatenAt: $this->eatenAt,
            notes: $this->notes,
        );
    }

    public function toAttributes(): array
    {
        return [
            'title' => $this->title,
            'items' => array_map(fn (MealItemDto $i) => $i->toArray(), $this->items),
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
        ];
    }

    private static function float(mixed $v): ?float
    {
        return $v === null ? null : (float) $v;
    }
}
