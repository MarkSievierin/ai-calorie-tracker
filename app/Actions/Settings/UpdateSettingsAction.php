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
        $this->writer->put($dto->values);
        $this->settings->refresh();
        // Timezone changes regroup days.
        $this->energy->refresh();

        return $this->settings->all();
    }
}
