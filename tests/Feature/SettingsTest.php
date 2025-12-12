<?php

use Coyotito\LaravelSettings\Facades\Settings;
use Coyotito\LaravelSettings\Facades\SettingsManager;

beforeEach(function () {
    rmdir_recursive(app_path('Settings'));
});

it('can mass update settings', function () {
    Settings::fake([
        'foo' => 'Foo',
        'bar' => 'Bar',
    ]);

    $settings = SettingsManager::resolveSettings(\Coyotito\LaravelSettings\Settings::DEFAULT_GROUP);

    expect($settings)
        ->update(['foo' => 'Bar', 'bar' => 'Foo'])
        ->not->toBe('Foo')
        ->foo->toBe('Bar')
        ->bar->toBe('Foo')
        ->update(['foo' => 'FooBar'])
        ->foo->toBe('FooBar')
        ->bar->toBe('Foo');
});

it('can mass update only defined settings', function () {
    Settings::fake([
        'foo' => 'Foo',
        'bar' => 'Bar',
    ]);

    $settings = SettingsManager::resolveSettings(\Coyotito\LaravelSettings\Settings::DEFAULT_GROUP);

    expect($settings)
        ->update(['foo' => 'Bar', 'bar' => 'Foo'])
        ->foo->not->toBe('Foo')
        ->foo->toBe('Bar')
        ->bar->not->toBe('Bar')
        ->bar->toBe('Foo')
        ->update(['foo' => 'FooBar', 'missing' => 'setting'])
        ->foo->toBe('FooBar')
        ->bar->toBe('Foo')
        ->not->toHaveProperty('missing')
        ->missing->not->toBe('setting')
        ->missing->toBeNull();
});
