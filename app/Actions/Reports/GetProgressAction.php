<?php

namespace App\Actions\Reports;

use App\Calc\WeightTrend;
use App\Services\EnergyService;
use App\Services\SettingsService;

/**
 * Progress over the last N complete days: trend and pace, average intake, adaptive TDEE, norm and flags.
 */
class GetProgressAction
{
    public function __construct(private readonly SettingsService $settings, private readonly EnergyService $energy) {}

    public function handle(int $days = 28): array
    {
        $today = $this->settings->today();
        $settings = $this->settings->all();
        $intake = $this->energy->intake();
        $counts = $this->energy->mealCounts();
        $trend = $this->energy->trend();
        $cp = $this->energy->model()->at($today);

        // Complete days only: [today - days, today - 1].
        $from = SettingsService::shift($today, -$days);
        $to = SettingsService::shift($today, -1);
        $window = SettingsService::dates($from, $to);
        $logged = array_values(array_filter($window, fn ($d) => isset($intake[$d])));
        $avgKcal = $logged ? round(array_sum(array_map(fn ($d) => $intake[$d], $logged)) / count($logged)) : null;

        $trendNow = $trend->at($today);
        $trendStart = $trend->at($from);

        return [
            'period' => ['from' => $from, 'to' => $to, 'days' => $days],
            'weight' => [
                'trend_now_kg' => $trendNow,
                'trend_period_start_kg' => $trendStart,
                'change_kg' => $trendNow !== null && $trendStart !== null ? round($trendNow - $trendStart, 2) : null,
                'pace_kg_per_week' => $trendNow !== null && $trendStart !== null ? round(($trendNow - $trendStart) / $days * 7, 2) : null,
                'target_weight_kg' => $settings['target_weight_kg'],
            ],
            'intake' => [
                'avg_kcal' => $avgKcal,
                'logged_days' => count($logged),
                'avg_meals_per_day' => round(array_sum(array_map(fn ($d) => $counts[$d] ?? 0, $window)) / max(1, $days), 1),
            ],
            'energy' => [
                'tdee_kcal' => $cp['tdee'],
                // start = formula or manual estimate, fact = from intake and weight trend
                'tdee_source' => $cp['source']->value,
                'tdee_reliable' => $cp['reliable'],
                'tdee_fact_last_kcal' => $cp['fact'],
                'start_estimate_kcal' => $cp['start_estimate'],
                'recalculated_on' => $cp['date'],
                'deficit_pct' => (float) $settings['deficit_pct'],
                'norm_kcal' => $cp['norm'],
                'min_kcal' => (float) $settings['min_kcal'],
            ],
            'flags' => $this->flags($today, $trend, $cp['tdee'], (float) $settings['min_kcal'], $avgKcal, $intake, $counts),
        ];
    }

    private function flags(string $today, WeightTrend $trend, ?float $tdee, float $minKcal, ?float $avgKcal, array $intake, array $counts): array
    {
        $at = fn (int $daysBack) => $trend->at(SettingsService::shift($today, -$daysBack));
        [$t0, $t7, $t14, $t21] = [$at(0), $at(7), $at(14), $at(21)];

        $week = SettingsService::dates(SettingsService::shift($today, -7), SettingsService::shift($today, -1));
        $mealsWeek = array_sum(array_map(fn ($d) => $counts[$d] ?? 0, $week));

        $last21 = SettingsService::dates(SettingsService::shift($today, -21), SettingsService::shift($today, -1));
        $logged21 = array_values(array_filter(array_map(fn ($d) => $intake[$d] ?? null, $last21), fn ($v) => $v !== null));
        $avg21 = $logged21 ? array_sum($logged21) / count($logged21) : null;

        return [
            // Losing more than 1 kg/week two weeks in a row (suggest eating more).
            'too_fast' => $t0 !== null && $t7 !== null && $t14 !== null && ($t7 - $t0) > 1.0 && ($t14 - $t7) > 1.0,
            // Trend flat for 3 weeks while intake is below TDEE.
            'stalled' => $t0 !== null && $t21 !== null && abs($t0 - $t21) < 0.2
                && $tdee !== null && $avg21 !== null && $avg21 < $tdee * 0.95,
            // Fewer than 2 meals per day logged over the last 7 complete days: data is not trustworthy.
            'low_logging' => $mealsWeek < 14,
            'below_min' => $avgKcal !== null && $avgKcal < $minKcal,
        ];
    }
}
