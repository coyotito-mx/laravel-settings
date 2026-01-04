<?php

use Coyotito\LaravelSettings\Exceptions\SettingsManifestCannotLoadException;
use Coyotito\LaravelSettings\Settings;
use Coyotito\LaravelSettings\SettingsManifest;

it('can load settings manifest', function () {
    $file = \Illuminate\Support\Facades\File::partialMock();
    $manifestPath = base_path('bootstrap/settings/manifest.php');
    $manifest = new SettingsManifest($file, $manifestPath);

    $file->shouldReceive('exists')
        ->with($manifestPath)
        ->andReturnTrue();

    $file->shouldReceive('getRequire')
        ->with($manifestPath)
        ->andReturn([
            Settings::DEFAULT_GROUP => 'App\\Settings\\DefaultSettings',
        ]);

    expect($manifest->load())
        ->toBeArray()
        ->toHaveKey(Settings::DEFAULT_GROUP)
        ->toContain('App\\Settings\\DefaultSettings');
});

it('can detect presence of manifest file', function () {
    /** @var string $manifestPath */
    $manifestPath = with(Storage::fake('manifest'), function ($storage) {
        $directory = 'settings';

        $storage->makeDirectory($directory);

        return $storage->path($directory . DIRECTORY_SEPARATOR . 'manifest.php');
    });

    $file = \Illuminate\Support\Facades\File::getFacadeRoot();
    $manifest = new SettingsManifest($file, $manifestPath);


    expect($manifest)
        ->present()
        ->toBeFalse();

    $file->put($manifestPath, '');

    expect($manifest)
        ->present()
        ->toBeTrue();
});

it('can generate settings manifest', function () {
    /** @var string $manifestPath */
    $manifestPath = with(Storage::fake('manifest'), function ($storage) {
        $directory = 'settings';

        $storage->makeDirectory($directory);

        return $storage->path($directory . DIRECTORY_SEPARATOR . 'manifest.php');
    });

    $file = \Illuminate\Support\Facades\File::partialMock();
    $manifest = new SettingsManifest($file, $manifestPath);

    $settingsArray = [
        Settings::DEFAULT_GROUP => 'App\\Settings\\DefaultSettings',
        'user' => 'App\\Settings\\UserSettings',
    ];

    $file->shouldReceive('put')
        ->withArgs(function ($path, $content) use ($manifestPath, $settingsArray) {
            expect($path)->toBe($manifestPath);

            $expectedContent = "<?php return " . var_export($settingsArray, true) . ";\n";

            expect($content)->toBe($expectedContent);

            return true;
        })
        ->andReturnTrue();

    $manifest->generate($settingsArray);
});

it('fail to load missing manifest', function () {
    $manifest = new SettingsManifest(\Illuminate\Support\Facades\File::getFacadeRoot(), '/non/existent/path/manifest.php');

    expect($manifest)
        ->present()
        ->toBeFalse()
        ->and(fn () => $manifest->load())
        ->toThrow(SettingsManifestCannotLoadException::class, 'Settings manifest not found at [/non/existent/path/manifest.php]');
});
