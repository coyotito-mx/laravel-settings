<?php

use Coyotito\LaravelSettings\Database\Schema\Blueprint;
use Coyotito\LaravelSettings\Facades\Settings;
use Coyotito\LaravelSettings\Facades\Schema;

use function Coyotito\LaravelSettings\Helpers\settings;

it('can add settings', function () {
    Settings::fake();

    Schema::default(function (Blueprint $group) {
        $group->add('foo', 'bar');
        $group->add('bar', 'baz');
        $group->add('baz', 'foobar');
    });

    expect(settings())
        ->get('foo')
        ->toBe('bar')
        ->get('bar')
        ->toBe('baz')
        ->get('baz')
        ->toBe('foobar');
});

it('can add settings to group', function () {
    Settings::fake(group: 'foo');

    Schema::in('foo', function (Blueprint $group) {
        $group->add('foo', 'bar');
        $group->add('bar', 'baz');
        $group->add('baz', 'foobar');
    });

    expect(settings()->group('foo'))
        ->get('foo')
        ->toBe('bar')
        ->get('bar')
        ->toBe('baz')
        ->get('baz')
        ->toBe('foobar');
});

it('can remove settings', function () {
    Settings::fake([
        'foo' => 'bar',
        'bar' => 'baz',
        'baz' => 'foobar',
    ]);

    Schema::delete(['bar']);

    expect(settings())
        ->get('foo')
        ->toBe('bar')
        ->get('bar')
        ->toBeNull()
        ->get('baz')
        ->toBe('foobar');
});

it('can remove settings from group', function () {
    Settings::fake([
        'foo' => 'bar',
        'bar' => 'baz',
        'baz' => 'foobar',
    ], group: 'foo');

    Schema::in('foo', function (Blueprint $group) {
        $group->remove('bar');
    });

    expect(settings()->group('foo'))
        ->get('foo')
        ->toBe('bar')
        ->get('bar')
        ->toBeNull()
        ->get('baz')
        ->toBe('foobar');
});

it('can drop settings', function () {
    Settings::fake([
        'foo' => 'bar',
        'bar' => 'baz',
        'baz' => 'foobar',
    ]);

    Schema::drop(\Coyotito\LaravelSettings\Settings::DEFAULT_GROUP);

    expect(settings())
        ->get('foo')
        ->toBeNull()
        ->get('bar')
        ->toBeNull()
        ->get('baz')
        ->toBeNull();
});

it('can drop settings from group', function () {
    Settings::fake([
        'foo' => 'bar',
        'bar' => 'baz',
        'baz' => 'foobar',
    ], group: 'foo');

    Schema::drop('foo');

    expect(settings()->group('foo'))
        ->get('foo')
        ->toBeNull()
        ->get('bar')
        ->toBeNull()
        ->get('baz')
        ->toBeNull();
});
