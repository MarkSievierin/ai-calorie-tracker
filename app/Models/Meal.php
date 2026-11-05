<?php

namespace App\Models;

use App\Enums\Confidence;
use App\Enums\MealSource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int $id
 * @property CarbonImmutable $eaten_at
 * @property string $title
 * @property list<array{name: string, grams: float|null, kcal: float}> $items
 * @property float $kcal_est
 * @property float $kcal_max upper bound of the estimate; day totals use it
 * @property float $protein_g
 * @property float $fat_g
 * @property float $carbs_g
 * @property float|null $sugar_g
 * @property float|null $fiber_g
 * @property float|null $sat_fat_g
 * @property float|null $salt_g
 * @property Confidence $confidence
 * @property MealSource $source
 * @property string|null $notes
 * @property CarbonImmutable|null $deleted_at
 */
class Meal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'eaten_at', 'title', 'items', 'kcal_est', 'kcal_max', 'protein_g', 'fat_g', 'carbs_g',
        'sugar_g', 'fiber_g', 'sat_fat_g', 'salt_g', 'confidence', 'source', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'eaten_at' => 'immutable_datetime',
            'items' => 'array',
            'kcal_est' => 'float',
            'kcal_max' => 'float',
            'protein_g' => 'float',
            'fat_g' => 'float',
            'carbs_g' => 'float',
            'sugar_g' => 'float',
            'fiber_g' => 'float',
            'sat_fat_g' => 'float',
            'salt_g' => 'float',
            'confidence' => Confidence::class,
            'source' => MealSource::class,
        ];
    }

    public function toApi(string $timezone): array
    {
        return [
            'id' => $this->id,
            'eaten_at' => $this->eaten_at->setTimezone($timezone)->toIso8601String(),
            'title' => $this->title,
            'items' => $this->items,
            'kcal_est' => $this->kcal_est,
            'kcal_max' => $this->kcal_max,
            'protein_g' => $this->protein_g,
            'fat_g' => $this->fat_g,
            'carbs_g' => $this->carbs_g,
            'sugar_g' => $this->sugar_g,
            'fiber_g' => $this->fiber_g,
            'sat_fat_g' => $this->sat_fat_g,
            'salt_g' => $this->salt_g,
            'confidence' => $this->confidence->value,
            'source' => $this->source->value,
            'notes' => $this->notes,
            'deleted' => $this->deleted_at !== null,
        ];
    }
}
