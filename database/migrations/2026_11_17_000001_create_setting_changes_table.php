<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // History of the settings that shape the norm (deficit, minimum): a change applies from the day it was made,
        // so past days keep the norm they had. `1970-01-01` = the value in effect before the first change.
        Schema::create('setting_changes', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50);
            $table->text('value')->nullable();
            $table->date('effective_on');
            $table->timestampTz('created_at')->nullable();
            $table->unique(['key', 'effective_on']);
        });

        // Backfill: a stored value that differs from the default was set on the day of its updated_at;
        // before that the default was in effect. The settings table keeps only the last change per key.
        $stored = DB::table('settings')->get()->keyBy('key');
        $setting = fn (string $key, mixed $default) => isset($stored[$key]) ? json_decode($stored[$key]->value, true) : $default;
        $tz = (string) $setting('timezone', 'Europe/Kyiv');
        $dayStart = (int) $setting('day_start_hour', 0);
        $defaults = ['deficit_pct' => 15, 'min_kcal' => 1800];

        foreach ($defaults as $key => $default) {
            $row = $stored[$key] ?? null;
            $value = $row ? json_decode($row->value, true) : null;
            if ($row === null || $row->updated_at === null || $value == $default) {
                continue;
            }
            $on = CarbonImmutable::parse($row->updated_at)->setTimezone($tz)->subHours($dayStart)->toDateString();
            $now = now();
            DB::table('setting_changes')->insert([
                ['key' => $key, 'value' => json_encode($default), 'effective_on' => '1970-01-01', 'created_at' => $now],
                ['key' => $key, 'value' => $row->value, 'effective_on' => $on, 'created_at' => $now],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_changes');
    }
};
