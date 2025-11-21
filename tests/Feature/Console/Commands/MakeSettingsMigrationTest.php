<?php

use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use Coyotito\LaravelSettings\Settings;

use function Pest\Laravel\artisan;

beforeAll(function () {
    expect()->extend('toBeClassSettings', function () {
        $segments = explode('\\', trim($this->value, '\\'));
        $root = array_shift($segments);

        expect($root)->toBe('App');

        $class = '\\'.implode('\\', [$root, ...$segments]);

        $file = array_pop($segments).'.php';
        $filepath = app_path(implode(DIRECTORY_SEPARATOR, [...$segments, $file]));

        expect($filepath)->toBeFile("The given namespace [{$this->value}] is not a file");

        $repo = Mockery::mock(Repository::class)->shouldIgnoreMissing();

        return expect(new $class($repo))->toBeInstanceOf(Settings::class);
    });
});

beforeEach(function () {
    rmdir_recursive(database_path('migrations'), dot_files: false);
    rmdir_recursive(app_path('Settings'));
});

it('can generate default migration', function () {
    expect('App\\Settings\\DefaultSettings')
        ->not->toBeClassSettings();

    artisan('make:settings')
        ->expectsOutputToContain('Migration for [default] group created')
        ->assertSuccessful();

    expect('App\\Settings\\DefaultSettings')
        ->toBeClassSettings();
});

it('can create settings with different class name', function () {
    expect('App\\Settings\\General')
        ->not->toBeClassSettings();

    artisan('make:settings', ['--class-name' => 'General'])
        ->assertSuccessful();

    expect('App\\Settings\\General')
        ->toBeClassSettings();
});

it('cannot replace already created migration', function () {
    artisan('make:settings')->assertSuccessful();

    expect(fn () => artisan('make:settings'))
        ->toThrow('The migration [default] settings already exists');
});

it('only generates the migration file', function () {
    artisan('make:settings', ['--without-class' => true])
        ->expectsOutputToContain('Migration for [default] group created')
        ->assertSuccessful();

    expect('App\\Settings\\DefaultSettings')
        ->not->toBeClassSettings();
});

it('cannot create group with reserved words')->todo();

