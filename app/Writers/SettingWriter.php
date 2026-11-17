<?php

namespace App\Writers;

use App\Models\Setting;
use App\Models\SettingChange;

class SettingWriter
{
    /** @param array<string, mixed> $values stored as JSON */
    public function put(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        }
    }

    /**
     * Dated change of a setting. The first change also keeps the old value as the one in effect "since always";
     * a second change on the same day replaces the first.
     */
    public function recordChange(string $key, mixed $old, mixed $new, string $date): void
    {
        SettingChange::query()->firstOrCreate(
            ['key' => $key, 'effective_on' => SettingChange::SINCE_ALWAYS],
            ['value' => json_encode($old)],
        );
        SettingChange::query()->updateOrCreate(['key' => $key, 'effective_on' => $date], ['value' => json_encode($new)]);
    }
}
