<?php

namespace App\Actions\Meals;

use App\Dto\MealDto;
use App\Models\Meal;
use App\Services\EnergyService;
use App\Services\MealItemsService;
use App\Writers\MealWriter;
use Illuminate\Validation\ValidationException;

class LogMealAction
{
    private const TOTALS = ['kcal_est', 'kcal_max', 'protein_g', 'fat_g', 'carbs_g'];

    public function __construct(
        private readonly MealWriter $writer,
        private readonly EnergyService $energy,
        private readonly MealItemsService $items,
    ) {}

    public function handle(MealDto $dto): Meal
    {
        if ($this->items->hasProducts($dto->items)) {
            $dto = $dto->withSum($this->items->sum($dto->items, $dto->sentTotals()));
        } else {
            $missing = array_diff(self::TOTALS, array_keys($dto->sentTotals()));
            if ($missing !== []) {
                throw ValidationException::withMessages(array_combine($missing, array_map(
                    fn (string $k) => "The $k field is required (or log items by product_id).",
                    $missing,
                )));
            }
            if ($dto->kcalMax < $dto->kcalEst) {
                throw ValidationException::withMessages(['kcal_max' => 'kcal_max must be >= kcal_est']);
            }
        }

        $meal = $this->writer->create($dto);
        $this->energy->refresh();

        return $meal;
    }
}
