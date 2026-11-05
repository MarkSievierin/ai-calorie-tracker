<?php

namespace App\Calc;

use Carbon\CarbonImmutable;

/**
 * Exponential moving average of daily weight.
 *
 * Several weigh-ins on one day are averaged first. A gap of g days between measurements uses
 * alpha_eff = 1 - (1 - alpha)^g, so missed days do not distort the trend. On days without a
 * measurement the trend keeps its last value.
 */
final class WeightTrend
{
    /** @var array<string, float> date => trend */
    private array $trend = [];

    private ?string $first = null;

    private ?string $last = null;

    /**
     * @param  array<string, float>  $dailyWeights  date (Y-m-d) => average weight that day
     */
    public function __construct(array $dailyWeights, private readonly float $alpha = 0.1)
    {
        ksort($dailyWeights);
        $prevDate = null;
        $value = null;
        foreach ($dailyWeights as $date => $weight) {
            if ($value === null) {
                $value = $weight;
                $this->first = $date;
            } else {
                $gap = max(1, self::daysBetween($prevDate, $date));
                $a = 1 - (1 - $this->alpha) ** $gap;
                $value += $a * ($weight - $value);
            }
            $this->trend[$date] = $value;
            $prevDate = $date;
        }
        $this->last = $prevDate;
    }

    /** Trend at the end of a date, or null before the first measurement. */
    public function at(string $date): ?float
    {
        if ($this->first === null || $date < $this->first) {
            return null;
        }
        if (isset($this->trend[$date])) {
            return round($this->trend[$date], 2);
        }
        // Carry the last known value forward.
        $best = null;
        foreach ($this->trend as $d => $v) {
            if ($d > $date) {
                break;
            }
            $best = $v;
        }

        return $best === null ? null : round($best, 2);
    }

    public function firstDate(): ?string
    {
        return $this->first;
    }

    public function lastDate(): ?string
    {
        return $this->last;
    }

    public static function daysBetween(string $from, string $to): int
    {
        return (int) CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to), false);
    }
}
