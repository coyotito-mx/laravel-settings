<?php

use Coyotito\LaravelSettings\Facades\Settings;
use Coyotito\LaravelSettings\Facades\SettingsManager;

beforeEach(function () {
    rmdir_recursive(app_path('Settings'));

    SettingsManager::clearRegisteredSettingsClasses();
});

it('can get settings', function () {
    $settings = Settings::fake([
        'foo' => 'bar',
        'bar' => 'baz',
    ]);

    expect($settings)
        ->foo->toBe('bar')
        ->bar->toBe('baz')
        ->get('foo')->toBe('bar')
        ->get('bar')->toBe('baz');
});

it('can update settings', function () {
    $settings = Settings::fake([
        'foo' => 'bar',
        'bar' => 'baz',
    ]);

    (
        fn () => expect($this->initialSettings)
            ->toBe(['foo' => 'bar', 'bar' => 'baz'])
            ->and($this)
            ->get('foo')->toBe('bar')
            ->get('bar')->toBe('baz')
    )->call($settings);

    $settings->foo = 'Foo';

    expect($settings)
        ->get('foo')->toBe('Foo')
        ->get('bar')->toBe('baz')
        ->save();

    (
        fn () => expect($this->initialSettings)
            ->toBe(['foo' => 'Foo', 'bar' => 'baz'])
            ->and($this)
            ->get('foo')->toBe('Foo')
            ->get('bar')->toBe('baz')
    )->call($settings);
});

it('can mass update settings', function () {
    $settings = Settings::fake([
        'foo' => 'Foo',
        'bar' => 'Bar',
    ]);

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
    $settings = Settings::fake([
        'foo' => 'Foo',
        'bar' => 'Bar',
    ]);

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
