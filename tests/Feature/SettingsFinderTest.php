<?php

use Coyotito\LaravelSettings\Finders\SettingsFinder;
use Illuminate\Support\Facades\File;
use Mockery\LegacyMockInterface;
use function Pest\Laravel\artisan;

beforeEach(function () {
   rmdir_recursive(app_path('Settings'));
});

afterEach(function () {
    rmdir_recursive(app_path('Settings'));
});

it('can find settings', function () {
    $files = File::partialMock();
    $finder = new SettingsFinder($files);

    artisan('make:settings-class', ['name' => 'DefaultSettings']);
    artisan('make:settings-class', ['name' => 'TestSettings', '--group' => 'test']);

    expect($finder)
        ->discover('App\\Settings')
        ->not()->toBeNull()
        ->toBeArray()
        ->toHaveCount(2);
});

it('cannot find settings', function () {
    $files = File::partialMock();
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
