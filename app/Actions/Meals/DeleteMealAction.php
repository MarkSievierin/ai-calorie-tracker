<?php

namespace App\Actions\Meals;

use App\Models\Meal;
use App\Readers\MealReader;
use App\Services\EnergyService;
use App\Writers\MealWriter;

/** Soft delete: the meal can be restored. */
class DeleteMealAction
{
    public function __construct(
        private readonly MealReader $reader,
        private readonly MealWriter $writer,
        private readonly EnergyService $energy,
    ) {}

    public function handle(int $id): Meal
    {
        $meal = $this->reader->findOrFail($id);
        $this->writer->delete($meal);
        $this->energy->refresh();

        return $meal;
    }
}
