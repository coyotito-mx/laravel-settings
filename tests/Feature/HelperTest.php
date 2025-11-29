<?php

use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use Mockery\LegacyMockInterface;
use Orchestra\Testbench;

use function Coyotito\LaravelSettings\Helpers\package_path;
use function Coyotito\LaravelSettings\Helpers\psr4_namespace_to_path;
use function Coyotito\LaravelSettings\Helpers\settings;

dataset('paths', [
    'config',
    'database',
    'stubs',
    'src',
    'src/helpers.php',
    'src/Settings.php',
    'src/SettingsServiceProvider.php',
    'composer.json',
    'composer.lock',
    'README.md',
]);

test('package path', function (string $path) {
    expect(package_path($path))
        ->toBe(Testbench\package_path($path))
        ->toBeFile();
})->with('paths');

it('converts psr-4 namespace to path', function () {
    File::partialMock()
        ->shouldReceive('json')
        ->with(base_path('composer.json'))
        ->andReturn([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'app/',
                    'Custom\\Settings\\' => 'app/Settings/',
                ],
            ],
        ]);

    expect(psr4_namespace_to_path('App\\Models'))
        ->toBe(app_path('Models'))
        ->and(psr4_namespace_to_path('Custom\\Settings\\Config'))
        ->toBe(app_path('Settings/Config'));
});

it('returns null for non-matching namespace', function () {
    File::partialMock()
        ->shouldReceive('json')
        ->with(base_path('composer.json'))
        ->andReturn([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'app/',
                ],
            ],
        ]);

    expect(psr4_namespace_to_path('NonExistent\\Namespace'))
        ->toBeNull();
});

describe('settings helper', function () {
    beforeEach(function () {
        app()->bind(Repository::class, function (){
            $mock = $this->mock(Repository::class)->makePartial();

            $mock->shouldReceive('setGroup')
                ->with('default')
                ->andReturn();

            return $mock;
        });
    });

    it('returns settings manager instance', function () {
        expect(settings())
            ->toBeInstanceOf(Coyotito\LaravelSettings\SettingsManager::class);
    });

    it('treats settings(setting) as get, not set', function () {
        expect(settings('key'))
            ->toBe('value');
    });

    it('treats settings(setting, default) as get with default, not set', function () {
        expect(settings('non_existent_key', 'default_value'))
            ->toBe('default_value')
            ->and(settings('key', 'default_value'))
            ->toBe('value');
    });

    it('treats settings(array<string>) as get, not set', function () {
        expect(settings(['key1', 'key2']))
            ->toBe(['key1' => 'value1', 'key2' => 'value2']);
    });

    it('treats settings(array<string>, default) as get, not set', function () {
        expect(settings(['key1', 'key2'], null))
            ->toBe(['key1' => 'value1', 'key2' => null]);
    });

    it('treats settings(array<string, mixed>) as set, not get', function () {
        settings(['key1' => 'value1', 'key2' => 'value2']);

        expect(settings(['key1', 'key2']))
            ->toBe(['key1' => 'value1', 'key2' => 'value2']);
    });

    it('treats settings(setting, array<string, mixed>) as set in group, not get', function () {
        settings('group', ['key1' => 'value1', 'key2' => 'value2']);

        expect(settings('group', ['key1', 'key2']))
            ->toBe(['key1' => 'value1', 'key2' => 'value2']);
    });
})->todo();
