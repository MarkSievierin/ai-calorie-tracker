<?php

namespace App\Actions\Reports;

use App\Readers\WeightReader;
use App\Services\EnergyService;
use App\Services\SettingsService;

/** Last weight, current trend and its change over 7 days. */
class GetWeightBriefAction
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly WeightReader $weights,
        private readonly EnergyService $energy,
    ) {}

    public function handle(): array
    {
        $today = $this->settings->today();
        $trend = $this->energy->trend();
        $now = $trend->at($today);
        $weekAgo = $trend->at(SettingsService::shift($today, -7));
        $last = $this->weights->latest();

        return [
            'weight_kg' => $last?->weight_kg,
            'measured_on' => $last ? $this->settings->localDate($last->measured_at) : null,
            'target_weight_kg' => $this->settings->get('target_weight_kg'),
            'trend_kg' => $now,
            'trend_7d_ago_kg' => $weekAgo,
            'trend_change_week_kg' => $now !== null && $weekAgo !== null ? round($now - $weekAgo, 2) : null,
        ];
    }
}
