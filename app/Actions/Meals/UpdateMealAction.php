<?php

namespace App\Actions\Meals;

use App\Dto\MealUpdateDto;
use App\Models\Meal;
use App\Readers\MealReader;
use App\Services\EnergyService;
use App\Writers\MealWriter;
use Illuminate\Validation\ValidationException;

class UpdateMealAction
{
    public function __construct(
        private readonly MealReader $reader,
        private readonly MealWriter $writer,
        private readonly EnergyService $energy,
    ) {}

    public function handle(int $id, MealUpdateDto $dto): Meal
    {
        $meal = $this->reader->findOrFail($id);
        $attributes = $dto->toAttributes();

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
