<?php

namespace App\Readers;

use App\Models\Setting;
use App\Models\SettingChange;

class SettingReader
{
    /** @return array<string, mixed> stored values (JSON-decoded) by key */
    public function stored(): array
    {
        return Setting::query()->pluck('value', 'key')->map(fn ($v) => json_decode($v, true))->all();
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, array<string, mixed>> key => [effective_on => value], oldest first
     */
    public function history(array $keys): array
    {
        $out = [];
        foreach (SettingChange::query()->whereIn('key', $keys)->orderBy('effective_on')->get() as $c) {
            $out[$c->key][substr((string) $c->effective_on, 0, 10)] = json_decode((string) $c->value, true);
        }

        return $out;
    }
}
