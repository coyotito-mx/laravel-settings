<?php

use function Pest\Laravel\artisan;

beforeEach(function () {
    rmdir_recursive(app_path('Custom'));
    rmdir_recursive(app_path('Settings'), delete_root: false);
});

afterEach(function () {
    rmdir_recursive(app_path('Custom'));
    rmdir_recursive(app_path('Settings'), delete_root: false);
});

it('create settings class', function () {
    expect(App\Settings\DefaultSettings::class)->not->toBeClassSettings();

    artisan('make:settings-class', ['name' => 'DefaultSettings'])
        ->doesntExpectOutputToContain('Failed to create settings class [DefaultSettings].')
        ->expectsOutputToContain('Settings class [DefaultSettings] created successfully.')
        ->assertSuccessful();

    expect(App\Settings\DefaultSettings::class)
        ->toBeClassSettings()
        ->and(
            File::get(app_path('Settings/DefaultSettings.php'))
        )->toContain('namespace App\Settings;');
});

it('create settings class in custom namespace', function () {
    expect(App\Custom\Settings\CustomSettings::class)->not->toBeClassSettings();

    artisan('make:settings-class', ['name' => 'CustomSettings', '--namespace' => 'App\\Custom\\Settings'])
        ->doesntExpectOutputToContain('Failed to create settings class [CustomSettings].')
        ->doesntExpectOutputToContain('The namespace [App\Custom\Settings] does not exist.')
        ->expectsOutputToContain('Settings class [CustomSettings] created successfully.')
        ->assertSuccessful();

    expect(App\Custom\Settings\CustomSettings::class)
        ->toBeClassSettings()
        ->and(
            File::get(app_path('Custom/Settings/CustomSettings.php'))
        )->toContain('namespace App\Custom\Settings;');
});

it('create settings class with specific group', function () {
    expect(App\Settings\DefaultSettings::class)->not->toBeClassSettings();

    artisan('make:settings-class', ['--group' => 'my-group'])
        ->expectsQuestion('Enter the name of the settings class', 'DefaultSettings')
        ->doesntExpectOutputToContain('Failed to create settings class [DefaultSettings].')
        ->expectsOutputToContain('Settings class [DefaultSettings] created successfully.')
        ->assertSuccessful();

    expect(App\Settings\DefaultSettings::class)
        ->toBeClassSettings()
        ->and(
            File::get(app_path('Settings/DefaultSettings.php'))
        )->toContain("return 'my-group';");
});

it('fails if class already exists', function () {
    artisan('make:settings-class', ['name' => 'DefaultSettings'])
        ->assertSuccessful();

    artisan('make:settings-class', ['name' => 'DefaultSettings'])
        ->expectsOutputToContain('Failed to create settings class [DefaultSettings].')
        ->assertFailed();
});

it('cannot create non-namespaced class', function () {
    expect(
        fn () =>
        artisan('make:settings-class', ['name' => 'NonNamespaced', '--namespace' => ''])->assertFailed()
    )->toThrow(InvalidArgumentException::class, 'The namespace [] does not exist.');
});

it('cannot create class with reserved name', function () {
    expect(
        fn () =>
        artisan('make:settings-class', ['name' => 'Settings'])
    )->toThrow(InvalidArgumentException::class, 'The provided name [Settings] is reserved.');
});
