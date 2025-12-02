<?php

namespace Workbench\App\Providers;

use Coyotito\LaravelSettings\Facades\SettingsManager;
use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        SettingsManager::addNamespace('Workbench\\App\\Settings');
    }
}
