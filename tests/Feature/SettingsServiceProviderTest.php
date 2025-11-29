<?php

use Coyotito\LaravelSettings\Settings;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\artisan;

beforeEach(function () {
    rmdir_recursive(database_path('migrations'), delete_root: false);
});

afterEach(function () {
    rmdir_recursive(database_path('migrations'), delete_root: false);
});

it('register settings', function () {
    artisan('make:settings', ['class' => 'LocalSettings', '--without-migration' => true])->assertSuccessful();
    artisan('vendor:publish', ['--tag' => 'laravel-settings-migrations']);

    $this->refreshApplication();

    artisan('migrate');

    expect(app()->make(App\Settings\LocalSettings::class))
        ->toBeInstanceOf(Settings::class);
});

it('register settings in config', function () {
    artisan('make:settings', ['class' => 'LocalSettings', '--namespace' => 'App\\Custom\\Settings', '--without-migration' => true])->assertSuccessful();
    artisan('vendor:publish', ['--tag' => 'laravel-settings-migrations']);

    $this->refreshApplication();

    artisan('migrate');

    Config::set('settings.classes', [
        App\Custom\Settings\LocalSettings::class,
    ]);

    expect(app()->make(App\Custom\Settings\LocalSettings::class))
        ->toBeInstanceOf(Settings::class);
});
