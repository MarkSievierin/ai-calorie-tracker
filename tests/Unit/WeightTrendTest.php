<?php

namespace Tests\Unit;

use App\Calc\WeightTrend;
use PHPUnit\Framework\TestCase;

class WeightTrendTest extends TestCase
{
    public function test_first_measurement_is_the_trend(): void
    {
        $t = new WeightTrend(['2026-01-01' => 82.0]);
        $this->assertSame(82.0, $t->at('2026-01-01'));
        $this->assertNull($t->at('2025-12-31'));
    }

    public function test_daily_ema_and_carry_forward(): void
    {
        $t = new WeightTrend(['2026-01-01' => 100.0, '2026-01-02' => 90.0], 0.1);
        $this->assertSame(99.0, $t->at('2026-01-02'));
        // No measurement: the last value is carried forward.
        $this->assertSame(99.0, $t->at('2026-01-10'));
    }

    public function test_gap_uses_compounded_alpha(): void
    {
        // A 2-day gap equals two daily steps towards the same value: 1 - 0.9^2 = 0.19.
        $t = new WeightTrend(['2026-01-01' => 100.0, '2026-01-03' => 90.0], 0.1);
        $this->assertSame(98.1, $t->at('2026-01-03'));
    }
}
