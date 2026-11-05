<?php

namespace App\Actions\Meals;

use App\Models\Meal;
use App\Readers\MealReader;
use App\Services\EnergyService;
use App\Writers\MealWriter;

class RestoreMealAction
{
    public function __construct(
        private readonly MealReader $reader,
        private readonly MealWriter $writer,
        private readonly EnergyService $energy,
    ) {}

    public function handle(int $id): Meal
    {
        $meal = $this->writer->restore($this->reader->findWithTrashedOrFail($id));
        $this->energy->refresh();

        return $meal;
    }
}
