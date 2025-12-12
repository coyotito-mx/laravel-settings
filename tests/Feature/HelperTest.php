<?php

use Composer\Autoload\ClassLoader;
use Coyotito\LaravelSettings\Facades\Settings;
use Orchestra\Testbench;

use function Coyotito\LaravelSettings\Helpers\package_path;
use function Coyotito\LaravelSettings\Helpers\psr4_namespace_normalizer;
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

describe('package path helper', function () {
    it('resolve package path', function (string $path) {
        expect(package_path($path))
            ->toBe(Testbench\package_path($path))
            ->toBeFile();
    })->with('paths');

    it('join multiple segments to the package path', function () {
        expect(package_path('src', 'Settings', 'Settings.php'))
            ->toBe(Testbench\package_path('src', 'Settings', 'Settings.php'));
    });

    it('handle empty segments', function () {
        expect(package_path('', 'src', '', 'Settings', '', 'Settings.php', ''))
            ->toBe(Testbench\package_path('src', 'Settings', 'Settings.php'));
    });
});

describe('psr4 namespace to path helper', function () {
    beforeEach(function () {
        $loader = $this->app->make('class-loader');

        $classLoader = Mockery::mock($loader)->makePartial();

        $classLoader
            ->shouldReceive('getPrefixesPsr4')
            ->andReturn([
                'Coyotito\\SettingsManager\\' => [package_path('src')]
            ]);

        $this->app->extend('class-loader', fn (ClassLoader $loader) => $classLoader);
    });

    it('resolves root namespace to path', function () {
        expect(psr4_namespace_to_path('Coyotito\\SettingsManager'))
            ->toBe(package_path('src'));
    });

    it('resolves sub-namespace to path', function () {
        expect(psr4_namespace_to_path('Coyotito\\SettingsManager\Helpers'))
            ->toBe(package_path('src', 'Helpers'));
    });

    it('returns null for non-existent namespace', function () {
        expect(psr4_namespace_to_path('NonExistent\Namespace'))
            ->toBeNull();
    });
});

describe('psr4 namespace normalizer', function () {
    it('normalize namespaces', function (string $namespace, string $normalized) {
        expect(psr4_namespace_normalizer($namespace))->toBe($normalized);
    })->with([
        ['Coyotito\\SettingsManager', 'Coyotito\\SettingsManager\\'],
        ['Coyotito\\Settings', 'Coyotito\\Settings\\'],
        ['Coyotito\\Helper', 'Coyotito\\Helper\\'],
        ['Coyotito\\Helpers', 'Coyotito\\Helpers\\'],
        ['Coyotito\\Custom\\Namespace', 'Coyotito\\Custom\\Namespace\\'],
    ]);

    it('normalize namespaces with already trailing separators', function (string $namespace, string $normalized) {
        expect(psr4_namespace_normalizer($namespace))->toBe($normalized);
    })->with([
        ['Coyotito\\SettingsManager\\', 'Coyotito\\SettingsManager\\'],
        ['\\Coyotito\\Settings\\', 'Coyotito\\Settings\\'],
        ['\\\\Coyotito\\Helper', 'Coyotito\\Helper\\'],
        ['\\Coyotito\\Helpers\\\\', 'Coyotito\\Helpers\\'],
        ['\\Coyotito\\\\Custom\\Namespace\\', 'Coyotito\\\\Custom\\Namespace\\'],
    ]);
});

describe('settings helper', function () {
    it('returns settings manager instance', function () {
        expect(settings())
            ->toBeInstanceOf(Coyotito\LaravelSettings\SettingsService::class);
    });

    it('treats settings(setting) as get, not set', function () {
        Settings::fake([
            'key' => 'value',
        ]);

        expect(settings('key'))
            ->toBe('value');
    });

    it('treats settings(setting, default) as get with default, not set', function () {
        Settings::fake([
            'key' => 'value',
        ]);

        expect(settings('non_existent_key', 'default_value'))
            ->toBe('default_value')
            ->and(settings('key', 'default_value'))
            ->toBe('value');
    });

    it('treats settings(array<string>) as get, not set', function () {
        Settings::fake([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);

        expect(settings(['key1', 'key2']))
            ->toBe(['key1' => 'value1', 'key2' => 'value2']);
    });

    it('treats settings(array<string>, default) as get, not set', function () {
        Settings::fake([
            'key1' => 'value1',
        ]);

        expect(settings(['key1', 'key2']))
            ->toBe(['key1' => 'value1', 'key2' => null]);
    });

    it('treats settings(array<string, mixed>) as set, not get', function () {
        Settings::fake([
            'key1' => 'old_value1',
            'key2' => 'old_value2',
        ]);

        settings(['key1' => 'value1', 'key2' => 'value2']);

        expect(settings(['key1', 'key2']))
            ->toBe(['key1' => 'value1', 'key2' => 'value2']);
    });

    it('treats settings(setting, array<string, mixed>) as set in group, not get', function () {
        Settings::fake([
            'key1' => 'old_value1',
            'key2' => 'old_value2',
        ], 'group');

        settings('group', ['key1' => 'value1', 'key2' => 'value2']);

        expect(settings('group', ['key1', 'key2']))
            ->toBe(['key1' => 'value1', 'key2' => 'value2']);
    });
});
