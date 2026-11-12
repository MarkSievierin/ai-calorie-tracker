<?php

namespace App\Actions\Reports;

use App\Services\EnergyService;
use App\Services\SettingsService;

/** Settings plus what they currently result in (today's TDEE and norm), for the agent. */
class GetProfileAction
{
    public function __construct(private readonly SettingsService $settings, private readonly EnergyService $energy) {}

    public function handle(): array
    {
        $today = $this->settings->today();
        $now = $this->energy->model()->at($today);

        return [
            'settings' => $this->settings->all(),
            'today' => $today,
            'tdee_kcal' => $now['tdee'] ?? null,
            'norm_kcal' => $now['norm'] ?? null,
        ];
    }
}
