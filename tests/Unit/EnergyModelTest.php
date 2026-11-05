<?php

namespace Tests\Unit;

use App\Calc\EnergyModel;
use App\Calc\WeightTrend;
use App\Enums\TdeeSource;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class EnergyModelTest extends TestCase
{
    private const CFG = [
        'kcal_per_kg' => 7700, 'trend_alpha' => 0.1, 'warmup_days' => 14, 'window_days' => 28,
        'max_missing_share' => 0.3, 'keep_old' => 0.7, 'recalc_every_days' => 7,
    ];

    private function settings(array $over = []): array
    {
        return $over + [
            'height_cm' => 180, 'age' => 30, 'activity_factor' => 1.2, 'deficit_pct' => 15,
            'min_kcal' => 1800, 'start_tdee' => null, 'target_weight_kg' => null, 'timezone' => 'UTC',
        ];
    }

    /** @return array<string, float> */
    private function days(string $from, int $n, float $value): array
    {
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $out[CarbonImmutable::parse($from)->addDays($i)->toDateString()] = $value;
        }

        return $out;
    }

    public function test_start_estimate_matches_spec_example(): void
    {
        // W=90, H=180, A=30, k=1.2 -> BMR 1880, TDEE 2256, norm 2256 * 0.85 = 1917.6.
        $m = new EnergyModel('2026-01-01', '2026-01-01', [], new WeightTrend(['2026-01-01' => 90.0]), $this->settings(), self::CFG);
        $cp = $m->at('2026-01-01');
        $this->assertSame(2256.0, $cp['tdee']);
        $this->assertSame(TdeeSource::Start, $cp['source']);
        $this->assertSame(1918, $cp['norm']);
    }

    public function test_fact_tdee_with_stable_weight_equals_intake_blended(): void
    {
        $weights = $this->days('2026-01-01', 30, 100.0);
        $intake = $this->days('2026-01-01', 30, 2500.0);
        $m = new EnergyModel('2026-01-01', '2026-01-30', $intake, new WeightTrend($weights), $this->settings(), self::CFG);

        // Start estimate: (1000 + 1125 - 150 + 5) * 1.2 = 2376.
        $this->assertSame(2376.0, $m->at('2026-01-14')['tdee']);
        // Day 14 checkpoint (2026-01-15): 0.7 * 2376 + 0.3 * 2500 = 2413.2.
        $cp = $m->at('2026-01-15');
        $this->assertSame(TdeeSource::Fact, $cp['source']);
        $this->assertSame(2500.0, $cp['fact']);
        $this->assertSame(2413.0, $cp['tdee']);
        // Next week: 0.7 * 2413 + 0.3 * 2500 = 2439.1.
        $this->assertSame(2439.0, $m->at('2026-01-22')['tdee']);
    }

    public function test_weight_loss_lowers_fact_tdee_by_7700_per_kg(): void
    {
        $weights = [];
        for ($i = 0; $i < 30; $i++) {
            $weights[CarbonImmutable::parse('2026-01-01')->addDays($i)->toDateString()] = 100 - 0.05 * $i;
        }
        $trend = new WeightTrend($weights);
        $intake = $this->days('2026-01-01', 30, 2100.0);
        $m = new EnergyModel('2026-01-01', '2026-01-30', $intake, $trend, $this->settings(), self::CFG);

        $cp = $m->at('2026-01-15');
        $expected = 2100 - ($trend->at('2026-01-14') - $trend->at('2026-01-01')) * 7700 / 14;
        $this->assertEqualsWithDelta(round($expected), $cp['fact'], 0.01);
        $this->assertGreaterThan(2100, $cp['fact']);
    }

    public function test_window_with_too_many_empty_days_keeps_previous_value(): void
    {
        $weights = $this->days('2026-01-01', 30, 100.0);
        // Only 5 logged days out of 14.
        $intake = $this->days('2026-01-01', 5, 3000.0);
        $m = new EnergyModel('2026-01-01', '2026-01-30', $intake, new WeightTrend($weights), $this->settings(), self::CFG);

        $cp = $m->at('2026-01-15');
        $this->assertSame(2376.0, $cp['tdee']);
        $this->assertFalse($cp['reliable']);
    }

    public function test_norm_never_below_minimum(): void
    {
        $m = new EnergyModel('2026-01-01', '2026-01-01', [], new WeightTrend([]), $this->settings(['start_tdee' => 1900]), self::CFG);
        $this->assertSame(1800, $m->at('2026-01-01')['norm']);
    }

    public function test_no_weight_and_no_manual_estimate_means_unknown_norm(): void
    {
        $m = new EnergyModel('2026-01-01', '2026-01-01', ['2026-01-01' => 500.0], new WeightTrend([]), $this->settings(), self::CFG);
        $this->assertNull($m->at('2026-01-01')['norm']);
        $this->assertSame(TdeeSource::None, $m->at('2026-01-01')['source']);
    }
}
