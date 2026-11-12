<?php

namespace App\Actions\Meals;

use App\Dto\MealItemDto;
use App\Dto\MealUpdateDto;
use App\Models\Meal;
use App\Readers\MealReader;
use App\Services\EnergyService;
use App\Services\MealItemsService;
use App\Writers\MealWriter;
use Illuminate\Validation\ValidationException;

class UpdateMealAction
{
    public function __construct(
        private readonly MealReader $reader,
        private readonly MealWriter $writer,
        private readonly EnergyService $energy,
        private readonly MealItemsService $items,
    ) {}

    public function handle(int $id, MealUpdateDto $dto): Meal
    {
        $meal = $this->reader->findOrFail($id);
        $attributes = $dto->toAttributes();

        // New items with product_id: the totals are summed from the items again, like in log_meal.
        if ($dto->items !== null && $this->items->hasProducts($dto->items)) {
            $sum = $this->items->sum($dto->items, $dto->sentTotals());
            $attributes = array_merge($attributes, $sum, [
                'items' => array_map(fn (MealItemDto $i) => $i->toArray(), $sum['items']),
            ]);
        }

        // The upper bound must stay >= the estimate after merging with stored values.
        $est = $attributes['kcal_est'] ?? $meal->kcal_est;
        $max = $attributes['kcal_max'] ?? $meal->kcal_max;
        if ($max < $est) {
            throw ValidationException::withMessages(['kcal_max' => 'kcal_max must be >= kcal_est']);
        }

        $meal = $this->writer->update($meal, $attributes);
        $this->energy->refresh();

        return $meal;
    }
}
