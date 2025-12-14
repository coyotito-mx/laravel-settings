<?php

use Coyotito\LaravelSettings\Database\Schema\Blueprint;
use Coyotito\LaravelSettings\Facades\Settings;
use Coyotito\LaravelSettings\Facades\Schema;
use Coyotito\LaravelSettings\Settings\DynamicSettings;

it('can add settings', function () {
    $settings = with(
        Settings::fake(),
        function (DynamicSettings $settings) {
            Schema::default(function (Blueprint $group) {
                $group->add('foo', 'bar');
                $group->add('bar', 'baz');
                $group->add('baz', 'foobar');
            });

            return $settings->regenerate();
        }
    );

    expect($settings)
        ->get('foo')
        ->toBe('bar')
        ->get('bar')
        ->toBe('baz')
        ->get('baz')
        ->toBe('foobar');
});

it('can add settings to group', function () {

    $settings = with(
        Settings::fake(),
        function (DynamicSettings $settings) {
            Schema::in('foo', function (Blueprint $group) {
                $group->add('foo', 'bar');
                $group->add('bar', 'baz');
                $group->add('baz', 'foobar');
            });

            return $settings->regenerate();
        }
    );

    expect($settings)
        ->get('foo')
        ->toBe('bar')
        ->get('bar')
        ->toBe('baz')
        ->get('baz')
        ->toBe('foobar');
});

it('can remove settings', function () {
    $settings = Settings::fake([
        'foo' => 'bar',
        'bar' => 'baz',
        'baz' => 'foobar',
    ]);

    Schema::delete(['bar']);

    expect($settings->regenerate())
        ->get('foo')
        ->toBe('bar')
        ->get('bar')
        ->toBeNull()
        ->get('baz')
        ->toBe('foobar');
});

it('can remove settings from group', function () {
    $settings = Settings::fake([
        'foo' => 'bar',
        'bar' => 'baz',
        'baz' => 'foobar',
    ], group: 'foo');

    Schema::in('foo', function (Blueprint $group) {
        $group->remove('bar');
    });

    expect($settings->regenerate())
        ->get('foo')
        ->toBe('bar')
        ->get('bar')
        ->toBeNull()
        ->get('baz')
        ->toBe('foobar');
});

it('can drop settings', function () {
    $settings = Settings::fake([
        'foo' => 'bar',
        'bar' => 'baz',
        'baz' => 'foobar',
    ]);

    Schema::drop(\Coyotito\LaravelSettings\Settings::DEFAULT_GROUP);

    expect($settings->regenerate())
        ->get('foo')
        ->toBeNull()
        ->get('bar')
        ->toBeNull()
        ->get('baz')
        ->toBeNull();
});

it('can drop settings from group', function () {
    $settings = Settings::fake([
        'foo' => 'bar',
        'bar' => 'baz',
        'baz' => 'foobar',
    ], group: 'foo');

    Schema::drop('foo');

    expect($settings->regenerate())
        ->get('foo')
        ->toBeNull()
        ->get('bar')
        ->toBeNull()
        ->get('baz')
        ->toBeNull();
});
