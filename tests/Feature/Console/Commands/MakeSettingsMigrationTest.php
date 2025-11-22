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
    rmdir_recursive(database_path('migrations'), delete_root: false);
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

it('creates a migration file for the default group', function () {
    artisan('make:settings')->assertSuccessful();

    $files = glob(database_path('migrations'.DIRECTORY_SEPARATOR.'*_create_default_settings.php'));

    expect($files)->not->toBeEmpty()
        ->and($files)->toHaveCount(1);
});

it('can create settings for a custom group', function () {
    expect('App\\Settings\\Billing')
        ->not->toBeClassSettings();

    artisan('make:settings', ['--group' => 'billing'])
        ->expectsOutputToContain('Migration for [billing] group created')
        ->assertSuccessful();

    expect('App\\Settings\\Billing')
        ->toBeClassSettings();

    $files = glob(database_path('migrations'.DIRECTORY_SEPARATOR.'*_create_billing_settings.php'));

    expect($files)->not->toBeEmpty()
        ->and($files)->toHaveCount(1);
});

it('can create setting class with different name than the group', function () {
    expect('App\\Settings\\SiteSettings')
        ->not->toBeClassSettings();

    artisan('make:settings', ['--class-name' => 'SiteSettings'])
        ->expectsOutputToContain('Migration for [default] group created')
        ->assertSuccessful();

    expect('App\\Settings\\SiteSettings')
        ->toBeClassSettings();

    $files = glob(database_path('migrations'.DIRECTORY_SEPARATOR.'*_create_default_settings.php'));

    expect($files)->not->toBeEmpty()
        ->and($files)->toHaveCount(1);
});

it('cannot create group with reserved words', function () {
    expect(function () {
        artisan('make:settings', ['--group' => 'settings']);
    })->toThrow(RuntimeException::class, "The provided name [settings] is reserved");

    $files = glob(database_path('migrations'.DIRECTORY_SEPARATOR.'*_create_settings_settings.php'));

    expect($files)->toBeEmpty();

    expect('App\\Settings\\Settings')
        ->not->toBeClassSettings();
});
