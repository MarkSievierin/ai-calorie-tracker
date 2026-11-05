<?php

namespace App\Actions\Reports;

use App\Dto\DateRangeDto;
use App\Models\Weight;
use App\Readers\WeightReader;
use App\Services\EnergyService;
use App\Services\SettingsService;

/** Raw weigh-ins and the daily trend line for a range. */
class GetWeightSeriesAction
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly WeightReader $weights,
        private readonly EnergyService $energy,
    ) {}

    public function handle(DateRangeDto $range): array
    {
        $tz = $this->settings->timezone();
        $trend = $this->energy->trend();
        [$from] = $this->settings->dayBounds($range->from);
        [, $to] = $this->settings->dayBounds($range->to);

        $line = [];
        foreach (SettingsService::dates($range->from, $range->to) as $d) {
            if (($t = $trend->at($d)) !== null) {
                $line[] = ['date' => $d, 'trend_kg' => $t];
            }
        }

        return [
            'points' => $this->weights->between($from, $to)->map(fn (Weight $w) => $w->toApi($tz))->all(),
            'trend' => $line,
        ];
    }
}
