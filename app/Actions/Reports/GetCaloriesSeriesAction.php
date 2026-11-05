<?php

namespace App\Actions\Reports;

use App\Dto\DateRangeDto;
use App\Services\EnergyService;
use App\Services\SettingsService;

/** One row per day for the calories chart: eaten, norm and TDEE in effect. */
class GetCaloriesSeriesAction
{
    public function __construct(private readonly EnergyService $energy) {}

    public function handle(DateRangeDto $range): array
    {
        $intake = $this->energy->intake();
        $counts = $this->energy->mealCounts();
        $model = $this->energy->model();

        return array_map(function (string $d) use ($intake, $counts, $model) {
            $cp = $model->at($d);

            return [
                'date' => $d,
                'kcal' => $intake[$d] ?? null,
                'meals' => $counts[$d] ?? 0,
                'norm_kcal' => $cp['norm'],
                'tdee_kcal' => $cp['tdee'],
            ];
        }, SettingsService::dates($range->from, $range->to));
    }
}
