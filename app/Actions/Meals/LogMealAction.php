<?php

namespace App\Actions\Meals;

use App\Dto\MealDto;
use App\Models\Meal;
use App\Services\EnergyService;
use App\Writers\MealWriter;

class LogMealAction
{
    public function __construct(private readonly MealWriter $writer, private readonly EnergyService $energy) {}

    public function handle(MealDto $dto): Meal
    {
        $meal = $this->writer->create($dto);
        $this->energy->refresh();

        return $meal;
    }
}
