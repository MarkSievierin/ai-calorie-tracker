<?php

namespace App\Writers;

use App\Dto\MealDto;
use App\Models\Meal;

class MealWriter
{
    public function create(MealDto $dto): Meal
    {
        return Meal::query()->create($dto->toAttributes());
    }

    public function update(Meal $meal, array $attributes): Meal
    {
        $meal->fill($attributes)->save();

        return $meal;
    }

    public function delete(Meal $meal): void
    {
        $meal->delete();
    }

    public function restore(Meal $meal): Meal
    {
        $meal->restore();

        return $meal;
    }
}
