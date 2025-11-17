<?php

declare(strict_types=1);

namespace {{namespace}};

use Illuminate\Support\ServiceProvider;

class {{package|title}}ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('testing', fn () => new {{package|title}}());
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
