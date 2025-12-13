<?php

use Coyotito\LaravelSettings\Facades\SettingsManager;
use Coyotito\LaravelSettings\Settings;

use function Pest\Laravel\artisan;

beforeEach(function () {
    rmdir_recursive(app_path('Custom'));
    rmdir_recursive(app_path('Settings'));

    \Coyotito\LaravelSettings\Facades\Settings::fake();

    SettingsManager::clearRegisteredSettingsClasses();
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

it('can register settings from namespace', function (string $class, string $namespace) {
    $class = makeUniqueClassName($class);

    /** @var \Coyotito\LaravelSettings\SettingsManager $settingsManager */
    $settingsManager = SettingsManager::getFacadeRoot();

    expect($settingsManager)
        ->resolveSettings('default')
        ->toBeNull();

    artisan('make:settings-class', ['name' => $class, '--namespace' => $namespace])
        ->assertSuccessful();

    $settingsManager->addNamespace($namespace);

    expect($settingsManager)
        ->resolveSettings('default')
        ->toBeInstanceOf($namespace.$class)
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

it('cannot register two settings with the same group', function () {
    $defaultClassName = makeUniqueClassName('DefaultSettings');
    $testClassName = makeUniqueClassName('TestSettings');

    artisan('make:settings-class', ['name' => $defaultClassName, '--group' => 'test'])
        ->assertSuccessful();

    artisan('make:settings-class', ['name' => $testClassName, '--group' => 'test'])
        ->assertSuccessful();

    expect(fn () => SettingsManager::addNamespace('App\\Settings\\'))
        ->toThrow(
            InvalidArgumentException::class,
            "Cannot register class '$testClassName', 'test' already registered by class '$defaultClassName'"
        );
});

it('cannot re-declare settings class', function (string $className) {
    $class = makeUniqueClassName($className);
    $settingsManager = SettingsManager::getFacadeRoot();

    artisan('make:settings-class', ['name' => $class, '--namespace' => $namespace = 'App\\Custom\\Settings\\']);

    $fqcn = $namespace.$class;
    $settingsManager->registerSettingsClass($fqcn);

    expect(fn () => $settingsManager->registerSettingsClass($fqcn))
        ->toThrow(
            InvalidArgumentException::class,
            "Settings group 'default' already registered by class '$class'"
        );
})->with('classnames');

it('cannot register unknown namespace', function () {
    $namespace = '\\None\\Existing\\Namespace\\';

    /** @var \Mockery\LegacyMockInterface&\Coyotito\LaravelSettings\SettingsManager $mock */
    $mock = $this->mock(\Coyotito\LaravelSettings\SettingsManager::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $mock->addNamespace($namespace);

    $mock->shouldHaveReceived('addNamespace', [$namespace]);
    $mock->shouldNotHaveReceived('registerSettingsClass');
    $mock->shouldNotHaveReceived('bindSettingsClass');
});

it('can register settings class', function (string $className) {
    $class = makeUniqueClassName($className);
    $settingsManager = SettingsManager::getFacadeRoot();

    artisan('make:settings-class', ['name' => $class, '--namespace' => $namespace = 'App\\Custom\\Settings\\']);

    $fqcn = $namespace.$class;
    $settingsManager->registerSettingsClass($fqcn);

    expect($settingsManager)
        ->resolveSettings('default')
        ->not->toBeNull()
        ->toBeInstanceOf($fqcn)
        ->toBeInstanceOf(Settings::class);
})->with('classnames');
