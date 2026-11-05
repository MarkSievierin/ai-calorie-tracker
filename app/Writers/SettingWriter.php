<?php

namespace App\Writers;

use App\Models\Setting;

class SettingWriter
{
    /** @param array<string, mixed> $values stored as JSON */
    public function put(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        }
    }
}
