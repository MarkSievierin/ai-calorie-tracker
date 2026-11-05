<?php

namespace App\Actions\Reports;

use App\Dto\DateRangeDto;
use App\Readers\MealReader;
use App\Readers\MetricReader;
use App\Services\EnergyService;
use App\Services\SettingsService;

/** Raw data per day for analysis: food totals, weight and trend, other metrics. */
class GetRawDaysAction
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly MealReader $meals,
        private readonly MetricReader $metrics,
        private readonly EnergyService $energy,
    ) {}

    /** @param list<string>|null $types */
    public function handle(DateRangeDto $range, ?array $types = null): array
    {
        $weights = $this->energy->dailyWeights();
        $trend = $this->energy->trend();
        [$from] = $this->settings->dayBounds($range->from);
        [, $to] = $this->settings->dayBounds($range->to);

        $food = [];
        foreach ($this->meals->between($from, $to) as $m) {
            $d = $this->settings->localDate($m->eaten_at);
            $food[$d] ??= ['kcal' => 0.0, 'protein_g' => 0.0, 'fat_g' => 0.0, 'carbs_g' => 0.0, 'meals' => 0];
            $food[$d]['kcal'] += $m->kcal_max;
            $food[$d]['protein_g'] += $m->protein_g;
            $food[$d]['fat_g'] += $m->fat_g;
            $food[$d]['carbs_g'] += $m->carbs_g;
            $food[$d]['meals']++;
        }

        $metrics = [];
        foreach ($this->metrics->between($range->from, $range->to, $types) as $m) {
            // Several values of one type on a day: the last one wins.
            $metrics[$m->date->format('Y-m-d')][$m->type] = $m->value;
        }

        return array_map(fn (string $d) => [
            'date' => $d,
            'food' => isset($food[$d]) ? array_map(fn ($v) => is_float($v) ? round($v, 1) : $v, $food[$d]) : null,
            'weight_kg' => $weights[$d] ?? null,
            'trend_kg' => $trend->at($d),
            'metrics' => $metrics[$d] ?? (object) [],
        ], SettingsService::dates($range->from, $range->to));
    }
}
