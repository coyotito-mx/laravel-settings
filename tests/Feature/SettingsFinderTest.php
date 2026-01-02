<?php

use Coyotito\LaravelSettings\Finders\SettingsFinder;
use Mockery\LegacyMockInterface;

it('can find settings', function () {
    $files = \Illuminate\Support\Facades\File::partialMock();
    $finder = tap(
        Mockery::mock(new SettingsFinder($files))->makePartial(),
        fn (LegacyMockInterface $finder) => $finder->shouldAllowMockingProtectedMethods()
    );

    $finder->shouldReceive('resolveNamespacePath')->andReturn(trim(app_path('Settings'), DIRECTORY_SEPARATOR));
    $files->shouldReceive('glob')->andReturn([
        app_path('Settings/SiteSettings.php'),
        app_path('Settings/UserSettings.php'),
    ]);

    expect($finder)
        ->discover('App\\Settings')
        ->not()->toBeNull()
        ->toBeArray()
        ->toHaveCount(2);
});

it('cannot find settings', function () {
    $files = \Illuminate\Support\Facades\File::partialMock();
    $finder = tap(
        Mockery::mock(new SettingsFinder($files))->makePartial(),
        fn (LegacyMockInterface $finder) => $finder->shouldAllowMockingProtectedMethods()
    );

    $finder->shouldReceive('resolveNamespacePath')->andReturn(null);
    $files->shouldReceive('glob')->once();

    expect($finder)
        ->discover('NonExistentSettings\\Namespace\\Settings') // This Namespace does not exist
        ->toBeNull()
        ->discover('App\\Settings') // This Namespace does exist, but does not contain any Settings
        ->toBeNull();
});
