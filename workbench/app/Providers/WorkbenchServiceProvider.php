<?php

namespace Workbench\App\Providers;

use Coyotito\LaravelSettings\Facades\LaravelSettings;
use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        LaravelSettings::addNamespace('Workbench\\App\\Settings');
    }
}
