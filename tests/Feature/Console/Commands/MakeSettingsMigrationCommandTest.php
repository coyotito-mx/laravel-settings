<?php

use Illuminate\Support\Facades\File;
use Pest\Expectation;

use function Orchestra\Sidekick\join_paths;
use function Pest\Laravel\artisan;

beforeEach(function () {
    rmdir_recursive(database_path('migrations'), delete_root: false);
});

afterEach(function () {
    rmdir_recursive(database_path('migrations'), delete_root: false);
});

it('create settings migration', function () {
    $migration = join_paths(database_path('migrations'), '*_add_settings_for_app.php');

    expect(File::glob($migration))->toBeEmpty();

    artisan('make:settings-migration')
        ->expectsQuestion('Enter the name of the settings migration', 'add_settings_for_app')
        ->doesntExpectOutputToContain('Failed to create settings migration [add_settings_for_app].')
        ->expectsOutputToContain('Settings migration [add_settings_for_app] created successfully.')
        ->assertSuccessful();

    expect(File::glob($migration))
        ->not->toBeEmpty()
        ->each(function (Expectation $file) {
            expect(File::get($file->value))
                ->toContain('Schema::default(function (Blueprint $group) {');
        });
});

it('create settings migration with specific group', function () {
    $migration = join_paths(database_path('migrations'), '*_add_notification_settings.php');

    expect(File::glob($migration))->toBeEmpty();

    artisan('make:settings-migration', ['name' => 'add_notification_settings', '--group' => 'notifications'])
        ->doesntExpectOutputToContain('Failed to create settings migration [add_notification_settings].')
        ->expectsOutputToContain('Settings migration [add_notification_settings] created successfully.')
        ->assertSuccessful();

    expect(File::glob($migration))
        ->not->toBeEmpty()
        ->each(function (Expectation $file) {
            expect(File::get($file->value))
                ->toContain('Schema::in(\'notifications\', function (Blueprint $group) {');
        });
});

it('create settings migration with specific group in migration name', function () {
    $migration = join_paths(database_path('migrations'), '*_site_configuration_settings_to_site_group.php');

    expect(File::glob($migration))->toBeEmpty();

    artisan('make:settings-migration', ['name' => 'site_configuration_settings_to_site_group'])
        ->doesntExpectOutputToContain('Failed to create settings migration [site_configuration_settings_to_site_group].')
        ->expectsOutputToContain('Settings migration [site_configuration_settings_to_site_group] created successfully.')
        ->assertSuccessful();

    expect(File::glob($migration))
        ->not->toBeEmpty()
        ->each(function (Expectation $file) {
            expect(File::get($file->value))
                ->toContain('Schema::in(\'site\', function (Blueprint $group) {');
        });
});

it('fails if migration already exists', function () {
    $migration = join_paths(database_path('migrations'), '*_add_settings_for_app.php');

    expect(File::glob($migration))->toBeEmpty();

    // First creation
    artisan('make:settings-migration', ['name' => 'add_settings_for_app'])
        ->doesntExpectOutputToContain('Failed to create settings migration [add_settings_for_app].')
        ->expectsOutputToContain('Settings migration [add_settings_for_app] created successfully.')
        ->assertSuccessful();

    expect(File::glob($migration))->not->toBeEmpty();

    expect(
        fn () =>
        artisan('make:settings-migration', ['name' => 'add_settings_for_app'])
    )->toThrow(RuntimeException::class, 'Migration [add_settings_for_app] already exists.');
});

it('cannot create migration with reserved name', function () {
    expect(
        fn () =>
        artisan('make:settings-migration', ['name' => 'add_settings_to_class_group'])
    )->toThrow(InvalidArgumentException::class, 'The provided group [class] is reserved.');
});
