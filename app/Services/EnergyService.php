<?php

namespace App\Services;

use App\Calc\EnergyModel;
use App\Calc\WeightTrend;
use App\Readers\MealReader;
use App\Readers\WeightReader;

/**
 * Daily aggregates of the whole history (by local date) and the models built on them.
 * History is small (one user), so it is recomputed per request: no stored state to drift.
 */
class EnergyService
{
    /** @var array{intake: array<string, float>, meals: array<string, int>, weights: array<string, float>}|null */
    private ?array $history = null;

    public function __construct(
        private readonly SettingsService $settings,
        private readonly MealReader $meals,
        private readonly WeightReader $weights,
    ) {}

    /** @return array<string, float> date => kcal_max eaten (only days with meals) */
    public function intake(): array
    {
        return $this->history()['intake'];
    }

    /** @return array<string, int> date => number of meals */
    public function mealCounts(): array
    {
        return $this->history()['meals'];
    }

    /** @return array<string, float> date => average weight */
    public function dailyWeights(): array
    {
        return $this->history()['weights'];
    }

    public function trend(): WeightTrend
    {
        return new WeightTrend($this->dailyWeights(), (float) config('kkal.energy.trend_alpha'));
    }

    public function model(): EnergyModel
    {
        $first = array_filter([array_key_first($this->intake()), array_key_first($this->dailyWeights())]);

        return new EnergyModel(
            startDate: $first ? min($first) : null,
            today: $this->settings->today(),
            intake: $this->intake(),
            trend: $this->trend(),
            settings: $this->settings->all(),
            cfg: config('kkal.energy'),
        );
    }

    /** Call after a write in the same request. */
    public function refresh(): void
    {
        $this->history = null;
    }

    private function history(): array
    {
        if ($this->history !== null) {
            return $this->history;
        }
        $intake = [];
        $count = [];
        foreach ($this->meals->allForTotals() as $m) {
            $d = $this->settings->localDate($m->eaten_at);
            $intake[$d] = ($intake[$d] ?? 0) + $m->kcal_max;
            $count[$d] = ($count[$d] ?? 0) + 1;
        }
        $weights = [];
        foreach ($this->weights->all() as $w) {
            $weights[$this->settings->localDate($w->measured_at)][] = $w->weight_kg;
        }

        return $this->history = [
            'intake' => $intake,
            'meals' => $count,
            'weights' => array_map(fn ($ws) => round(array_sum($ws) / count($ws), 2), $weights),
        ];
    }
}
