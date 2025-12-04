<?php

namespace Tests;

use Coyotito\LaravelSettings\SettingsServiceProvider;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithViews;

    protected function getPackageProviders($app): array
    {
        return [
            SettingsServiceProvider::class,
        ];
    }
}
