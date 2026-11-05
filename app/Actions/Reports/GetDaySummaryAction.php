<?php

namespace App\Actions\Reports;

use App\Models\Meal;
use App\Readers\MealReader;
use App\Services\EnergyService;
use App\Services\SettingsService;

/** Meals of a local day with totals (kcal by kcal_max), the norm in effect that day and what is left. */
class GetDaySummaryAction
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly MealReader $meals,
        private readonly EnergyService $energy,
    ) {}

    public function handle(?string $date = null): array
    {
        $date ??= $this->settings->today();
        $meals = $this->meals->between(...$this->settings->dayBounds($date));
        $tz = $this->settings->timezone();

        $sum = fn (string $f) => round($meals->sum($f), 1);
        // Optional nutrients: null when no meal has a value, so "unknown" is not shown as 0.
        $optional = function (string $f) use ($meals) {
            $known = $meals->whereNotNull($f);

            return $known->isEmpty() ? null : round($known->sum($f), 1);
        };

        $eaten = $sum('kcal_max');
        $norm = $this->energy->model()->at($date)['norm'];

        return [
            'date' => $date,
            'meals' => $meals->map(fn (Meal $m) => $m->toApi($tz))->values()->all(),
            'totals' => [
                'kcal' => $eaten,
                'kcal_est' => $sum('kcal_est'),
                'protein_g' => $sum('protein_g'),
                'fat_g' => $sum('fat_g'),
                'carbs_g' => $sum('carbs_g'),
                'sugar_g' => $optional('sugar_g'),
                'fiber_g' => $optional('fiber_g'),
                'sat_fat_g' => $optional('sat_fat_g'),
                'salt_g' => $optional('salt_g'),
            ],
            'norm_kcal' => $norm,
            'remaining_kcal' => $norm === null ? null : round($norm - $eaten, 1),
            'note' => 'kcal totals use kcal_max (upper estimate)',
        ];
    }
}
