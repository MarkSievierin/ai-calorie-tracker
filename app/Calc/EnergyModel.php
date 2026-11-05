<?php

namespace App\Calc;

use App\Enums\TdeeSource;
use Carbon\CarbonImmutable;

/**
 * Adaptive TDEE (calories per day that keep weight stable) and the daily norm.
 *
 * Recalculated on weekly checkpoints counted from the first day with data:
 * - first `warmup_days`: start estimate = manual start_tdee, or Mifflin-St Jeor (male) x activity factor;
 * - after that: TDEE_fact = avg kcal_max over logged days - (trend_end - trend_start) * 7700 / N
 *   over the last N complete days (N = up to window_days), blended as keep_old * old + (1 - keep_old) * fact.
 *   A window with too many days without food records is unreliable and keeps the previous TDEE.
 *
 * Because intake uses the upper estimate (kcal_max), a systematic overestimate of food also raises
 * TDEE_fact, so the two errors cancel out.
 *
 * The model is recomputed from history on every call: there is no stored state to drift.
 */
final class EnergyModel
{
    /** @var list<array<string, mixed>> */
    private array $checkpoints = [];

    /**
     * @param  array<string, float>  $intake  date => kcal_max eaten (only days with at least one meal)
     * @param  array<string, mixed>  $settings  values from Settings::all()
     * @param  array<string, mixed>  $cfg  config('kkal.energy')
     */
    public function __construct(
        private readonly ?string $startDate,
        private readonly string $today,
        private readonly array $intake,
        private readonly WeightTrend $trend,
        private readonly array $settings,
        private readonly array $cfg,
    ) {
        $this->build();
    }

    public function startEstimate(string $date): ?float
    {
        if ($this->settings['start_tdee'] !== null) {
            return (float) $this->settings['start_tdee'];
        }
        $w = $this->trend->at($date) ?? ($this->trend->lastDate() ? $this->trend->at($this->trend->lastDate()) : null);
        $h = $this->settings['height_cm'];
        $a = $this->settings['age'];
        if ($w === null || $h === null || $a === null) {
            return null;
        }
        $bmr = 10 * $w + 6.25 * (float) $h - 5 * (float) $a + 5;

        return round($bmr * (float) $this->settings['activity_factor']);
    }

    public function normFor(?float $tdee): ?int
    {
        if ($tdee === null) {
            return null;
        }
        $norm = $tdee * (1 - (float) $this->settings['deficit_pct'] / 100);

        return (int) round(max((float) $this->settings['min_kcal'], $norm));
    }

    /** Checkpoint in effect on a date (the last one on or before it). */
    public function at(string $date): array
    {
        $current = null;
        foreach ($this->checkpoints as $cp) {
            if ($cp['date'] > $date) {
                break;
            }
            $current = $cp;
        }

        return $current ?? $this->emptyCheckpoint($date);
    }

    /** @return list<array<string, mixed>> */
    public function checkpoints(): array
    {
        return $this->checkpoints;
    }

    private function build(): void
    {
        if ($this->startDate === null) {
            return;
        }
        $start = CarbonImmutable::parse($this->startDate);
        $today = CarbonImmutable::parse($this->today);
        $step = (int) $this->cfg['recalc_every_days'];
        $warmup = (int) $this->cfg['warmup_days'];
        $maxWindow = (int) $this->cfg['window_days'];

        $prev = null;
        for ($c = $start; $c <= $today; $c = $c->addDays($step)) {
            $date = $c->toDateString();
            $age = (int) $start->diffInDays($c);
            $estimate = $this->startEstimate($date);

            $old = $age < $warmup ? $estimate : ($prev['tdee'] ?? $estimate);
            $cp = [
                'date' => $date,
                'tdee' => $old,
                'source' => $old === null ? TdeeSource::None : ($age < $warmup ? TdeeSource::Start : ($prev['source'] ?? TdeeSource::Start)),
                'reliable' => $old !== null && $age < $warmup,
                'fact' => null,
                'window_days' => null,
            ];
            if ($age >= $warmup && $old !== null) {
                // Unreliable window: keep the previous value, marked as not reliable.
                $cp = $this->factCheckpoint($c, $start, $maxWindow, $old) ?? $cp;
            }

            $cp['start_estimate'] = $estimate;
            $cp['norm'] = $this->normFor($cp['tdee']);
            $this->checkpoints[] = $cp;
            $prev = $cp;
        }
    }

    /** Fact-based checkpoint, or null when the window cannot be trusted. */
    private function factCheckpoint(CarbonImmutable $c, CarbonImmutable $start, int $maxWindow, float $old): ?array
    {
        // Window = N complete days before the checkpoint: [c - N, c - 1].
        $n = min($maxWindow, (int) $start->diffInDays($c));
        $from = $c->subDays($n);
        $wStart = $this->trend->at($from->subDay()->toDateString()) ?? $this->trend->at($from->toDateString());
        $wEnd = $this->trend->at($c->subDay()->toDateString());

        $sum = 0.0;
        $logged = 0;
        for ($d = $from; $d < $c; $d = $d->addDay()) {
            $key = $d->toDateString();
            if (isset($this->intake[$key])) {
                $sum += $this->intake[$key];
                $logged++;
            }
        }
        $missingShare = $n > 0 ? ($n - $logged) / $n : 1;
        if ($logged === 0 || $wStart === null || $wEnd === null || $missingShare > $this->cfg['max_missing_share']) {
            return null;
        }

        $fact = $sum / $logged - ($wEnd - $wStart) * $this->cfg['kcal_per_kg'] / $n;
        $keep = (float) $this->cfg['keep_old'];

        return [
            'date' => $c->toDateString(),
            'tdee' => round($keep * $old + (1 - $keep) * $fact),
            'source' => TdeeSource::Fact,
            'reliable' => true,
            'fact' => round($fact),
            'window_days' => $n,
        ];
    }

    private function emptyCheckpoint(string $date): array
    {
        $estimate = $this->startEstimate($date);

        return [
            'date' => $date,
            'tdee' => $estimate,
            'source' => $estimate === null ? TdeeSource::None : TdeeSource::Start,
            'reliable' => $estimate !== null,
            'fact' => null,
            'window_days' => null,
            'start_estimate' => $estimate,
            'norm' => $this->normFor($estimate),
        ];
    }
}
