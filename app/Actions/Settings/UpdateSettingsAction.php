<?php

namespace App\Actions\Settings;

use App\Dto\SettingsUpdateDto;
use App\Services\EnergyService;
use App\Services\SettingsService;
use App\Writers\SettingWriter;

class UpdateSettingsAction
{
    public function __construct(
        private readonly SettingWriter $writer,
        private readonly SettingsService $settings,
        private readonly EnergyService $energy,
    ) {}

    /** @return array<string, mixed> all settings after the change */
    public function handle(SettingsUpdateDto $dto): array
    {
        $before = $this->settings->all();
        $this->writer->put($dto->values);
        $this->settings->refresh();

        // Deficit and minimum norm apply from today on: past days keep the norm they had.
        $today = $this->settings->today();
        foreach (config('kkal.dated_settings') as $key) {
            // Loose compare: 20 and 20.0 are the same deficit.
            if (array_key_exists($key, $dto->values) && $dto->values[$key] != $before[$key]) {
                $this->writer->recordChange($key, $before[$key], $dto->values[$key], $today);
            }
        }
        $this->settings->refresh();
        // Timezone and day start hour changes regroup days.
        $this->energy->refresh();

        return $this->settings->all();
    }
}
