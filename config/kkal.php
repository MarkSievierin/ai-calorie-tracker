<?php

return [
    // Static access token for MCP (?key=), REST (Authorization: Bearer) and the web app (cookie).
    'token' => env('KKAL_TOKEN'),

    // Failed auth attempts allowed per IP per minute.
    'auth_fail_limit' => 10,

    // Defaults for the settings table. A row in `settings` overrides the value here.
    'defaults' => [
        'timezone' => 'Europe/Kyiv',
        // Hour at which a new day starts. 4 = a snack at 01:00 still belongs to the previous day.
        'day_start_hour' => 0,
        // Personal values are set in the app (Settings); without them the start estimate needs start_tdee.
        'height_cm' => null,
        'age' => null,
        'target_weight_kg' => null,
        // Manual starting TDEE; null = Mifflin-St Jeor from trend weight, height, age and activity factor.
        'start_tdee' => null,
        'activity_factor' => 1.2,
        'deficit_pct' => 15,
        'min_kcal' => 1800,
    ],

    // Settings that shape the norm and keep a dated history: a change applies from the day it is made, past days
    // keep the norm they had. Profile values (height, age, activity, start TDEE) are corrections of facts, so a
    // change there recomputes the whole history on purpose.
    'dated_settings' => ['deficit_pct', 'min_kcal'],

    'energy' => [
        'kcal_per_kg' => 7700,
        'trend_alpha' => 0.1,
        // Days of history before the start estimate is replaced by the fact-based TDEE.
        'warmup_days' => 14,
        'window_days' => 28,
        // Share of days without food records that makes a window unreliable.
        'max_missing_share' => 0.3,
        // TDEE = keep * old + (1 - keep) * fact
        'keep_old' => 0.7,
        'recalc_every_days' => 7,
    ],
];
