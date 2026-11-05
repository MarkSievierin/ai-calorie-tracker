<?php

namespace App\Dto;

use App\Enums\Confidence;
use App\Enums\MealSource;
use Carbon\CarbonImmutable;

/** A new meal. Times are UTC. */
final readonly class MealDto
{
    /** @param list<MealItemDto> $items */
    public function __construct(
        public string $title,
        public array $items,
        public float $kcalEst,
        public float $kcalMax,
        public float $proteinG,
        public float $fatG,
        public float $carbsG,
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
            kcalEst: (float) $data['kcal_est'],
            kcalMax: (float) $data['kcal_max'],
            proteinG: (float) $data['protein_g'],
            fatG: (float) $data['fat_g'],
            carbsG: (float) $data['carbs_g'],
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
