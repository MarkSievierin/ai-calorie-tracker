<?php

namespace App\Readers;

use App\Models\Setting;

class SettingReader
{
    /** @return array<string, mixed> stored values (JSON-decoded) by key */
    public function stored(): array
    {
        return Setting::query()->pluck('value', 'key')->map(fn ($v) => json_decode($v, true))->all();
    }
}
