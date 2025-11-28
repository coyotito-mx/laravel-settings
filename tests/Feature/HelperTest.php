<?php

use Orchestra\Testbench;

use function Coyotito\LaravelSettings\Helpers\package_path;
use function Coyotito\LaravelSettings\Helpers\psr4_namespace_to_path;

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
