<?php

namespace App\Providers;

use App\Services\EnergyService;
use App\Services\SettingsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One instance per request; writes call refresh() so reads in the same request see them.
        $this->app->scoped(SettingsService::class);
        $this->app->scoped(EnergyService::class);
    }
}
