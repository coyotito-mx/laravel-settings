<?php

use Coyotito\LaravelSettings\Settings;

use function Pest\Laravel\artisan;

beforeEach(function () {
    rmdir_recursive(database_path('migrations'), delete_root: false);
});

afterEach(function () {
    rmdir_recursive(database_path('migrations'), delete_root: false);
});

it('register settings', function () {
    artisan('make:settings', ['--without-migration' => true, '--class-name' => 'LocalSettings'])->assertSuccessful();
    artisan('vendor:publish', ['--tag' => 'laravel-settings-migrations']);

    $this->refreshApplication();

    artisan('migrate');

    expect(app()->make(App\Settings\LocalSettings::class))
        ->toBeInstanceOf(Settings::class);
})->todo();
