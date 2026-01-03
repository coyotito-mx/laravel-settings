<?php

use Coyotito\LaravelSettings\Facades\SettingsManager;
use Coyotito\LaravelSettings\Repositories\InMemoryRepository;
use Coyotito\LaravelSettings\Settings;

use function Pest\Laravel\artisan;

beforeEach(function () {
    rmdir_recursive(app_path('Custom'));
    rmdir_recursive(app_path('Settings'));

    \Coyotito\LaravelSettings\Facades\Settings::swapRepository(new InMemoryRepository());
});

afterEach(function () {
    rmdir_recursive(app_path('Custom'));
    rmdir_recursive(app_path('Settings'));
});

dataset('classnames', [
    'DefaultSettings',
    'TestSettings',
    'SiteSettings',
    'UserSettings',
    'AdminSettings',
]);

it('resolve settings by FQCN', function (string $class) {
    /** @var \Coyotito\LaravelSettings\SettingsManager $manager */
    $manager = SettingsManager::getFacadeRoot();
    $classname = makeUniqueClassName($class);

    artisan('make:settings-class', ['name' => $classname]);

    $manager->loadSettings();

    expect($manager)
        ->resolveSettings("App\\Settings\\$classname")
        ->not->toBeNull()
        ->toBeInstanceOf(Settings::class);
})->with('classnames');

it('can register settings from namespace', function (string $class, string $namespace) {
    $classname = makeUniqueClassName($class);

    /** @var \Coyotito\LaravelSettings\SettingsManager $manager */
    $manager = SettingsManager::getFacadeRoot();

    expect($manager)
        ->resolveSettings('default')
        ->toBeNull();

    artisan('make:settings-class', ['name' => $classname, '--namespace' => $namespace])
        ->assertSuccessful();

    $manager->addNamespace($namespace);
    $manager->loadSettings();

    expect($manager)
        ->resolveSettings('default')
        ->toBeInstanceOf($namespace.$classname)
        ->toBeInstanceOf(Settings::class);
})->with([
    ['class' => 'DefaultSettings', 'namespace' => 'App\\Custom\\Settings\\'],
    ['class' => 'TestSettings',    'namespace' => 'App\\Custom\\Test\\Settings\\'],
    ['class' => 'SiteSettings',    'namespace' => 'App\\Custom\\Site\\Settings\\'],
    ['class' => 'UserSettings',    'namespace' => 'App\\Custom\\User\\Settings\\'],
    ['class' => 'AdminSettings',   'namespace' => 'App\\Custom\\Admin\\Settings\\'],
]);

it('can clear settings classes', function (string $class, string $group) {
    $class = makeUniqueClassName($class);

    /** @var \Coyotito\LaravelSettings\SettingsManager $settingsManager */
    $settingsManager = SettingsManager::getFacadeRoot();

    artisan('make:settings-class', ['name' => $class, '--group' => $group])
        ->assertSuccessful();

    $settingsManager->addNamespace('App\\Settings\\');
    $settingsManager->loadSettings();

    expect($settingsManager)
        ->resolveSettings($group)
        ->toBeInstanceOf(Settings::class);

    $settingsManager->clearRegisteredSettingsClasses();

    expect($settingsManager)
        ->resolveSettings($group)
        ->toBeNull();
})->with([
    ['class' => 'DefaultSettings', 'group' => Settings::DEFAULT_GROUP],
    ['class' => 'TestSettings',    'group' => 'test'],
    ['class' => 'SiteSettings',    'group' => 'site'],
    ['class' => 'UserSettings',    'group' => 'user'],
    ['class' => 'AdminSettings',   'group' => 'admin'],
]);

it('can register settings class', function (string $className) {
    $class = makeUniqueClassName($className);
    $manager = SettingsManager::getFacadeRoot();

    artisan('make:settings-class', ['name' => $class, '--namespace' => $namespace = 'App\\Custom\\Settings\\']);

    $fqcn = $namespace.$class;
    $manager->registerSettingsClass($fqcn);
    $manager->loadSettings();

    expect($manager)
        ->resolveSettings('default')
        ->not->toBeNull()
        ->toBeInstanceOf($fqcn)
        ->toBeInstanceOf(Settings::class);
})->with('classnames');
