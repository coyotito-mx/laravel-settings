<?php

use Orchestra\Testbench;

use function Coyotito\LaravelSettings\Helpers\package_path;

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
