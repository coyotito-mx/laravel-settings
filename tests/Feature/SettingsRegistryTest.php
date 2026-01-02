<?php

use Coyotito\LaravelSettings\Exceptions\GroupAlreadyRegisteredException;
use Coyotito\LaravelSettings\Exceptions\SettingsAlreadyRegisteredException;
use Coyotito\LaravelSettings\Finders\SettingsFinder;
use Coyotito\LaravelSettings\Repositories\InMemoryRepository;
use Coyotito\LaravelSettings\Settings;
use Coyotito\LaravelSettings\SettingsManifest;
use Coyotito\LaravelSettings\SettingsRegistry;

beforeEach(function () {
    $this->mockManifest = Mockery::mock(SettingsManifest::class);
    $this->mockFinder   = Mockery::mock(SettingsFinder::class);
    $this->registry    = new SettingsRegistry(
        $this->mockManifest,
        $this->mockFinder,
        $this->app,
    );
});

it('can register settings class', function () {
    $class = new class (new InMemoryRepository) extends Settings {};

    expect(
        $this->registry->registerSettings($fqcn = get_class($class))
    )
        ->toBeInstanceOf(SettingsRegistry::class)
        ->and($this->registry->settings)
        ->toHaveCount(1)
        ->toHaveKey(Settings::DEFAULT_GROUP)
        ->toContain($fqcn)
        ->and($this->app->bound('default'))
        ->and($this->app->bound($fqcn));
});

it('cannot register same group settings twice', function () {
    $class1 = new class (new InMemoryRepository) extends Settings {};
    $class2 = new class (new InMemoryRepository) extends Settings {};

    $this->registry->registerSettings(get_class($class1));

    expect(fn () => $this->registry->registerSettings(get_class($class2)))
        ->toThrow(GroupAlreadyRegisteredException::class);
});

it('cannot register same settings class twice', function () {
    $class = new class (new InMemoryRepository) extends Settings {};

    $this->registry->registerSettings(get_class($class));

    expect(fn () => $this->registry->registerSettings(get_class($class)))
        ->toThrow(SettingsAlreadyRegisteredException::class);
});

it('does not resolve unregistered settings', function () {
    expect($this->registry)
        ->resolveSettings(Settings::DEFAULT_GROUP)
        ->toBeNull();
});

it('can register namespace', function () {
    // Simulate finder discovery
    $this->mockFinder
        ->shouldReceive('discover')
        ->andReturn([
            $fqcn1 = get_class(new class (new InMemoryRepository) extends Settings {}),
            $fqcn2 = get_class(new class (new InMemoryRepository) extends Settings {
                public static function getGroup(): string
                {
                    return 'test';
                }
            }),
        ]);

    $this->mockManifest->shouldReceive('present')->andReturn(false);

    $this->registry->registerNamespace('App\\DummySettings');

    expect($this->registry)
        ->settings
        ->toBeEmpty();

    $this->registry->boot();

    expect($this->registry)
        ->settings
        ->toHaveCount(2);
});

it('can resolve settings by class-string', function () {
    \Coyotito\LaravelSettings\Facades\Settings::swapRepository($repo = new InMemoryRepository);
    $this->mockManifest->shouldReceive('present')->andReturn(false);
    $this->registry->registerSettings($fqcn = get_class(new class ($repo) extends Settings {}));

    $this->registry->boot();

    expect($this->registry->resolveSettings($fqcn))
        ->toBeInstanceOf($fqcn);
});

it('can resolve settings by group', function () {
    \Coyotito\LaravelSettings\Facades\Settings::swapRepository($repo = new InMemoryRepository);
    $this->mockManifest->shouldReceive('present')->andReturn(false);
    $this->registry->registerSettings($fqcn = get_class(new class ($repo) extends Settings {}));

    $this->registry->boot();

    expect($this->registry->resolveSettings(Settings::DEFAULT_GROUP))
        ->toBeInstanceOf($fqcn);
});

test('cold boot discovers and registers', function () {
    $this->mockManifest->shouldReceive('present')->andReturnFalse();
    $this->mockFinder->shouldReceive('discover')->andReturn([
        $fqcn = get_class(new class (new InMemoryRepository) extends Settings {}),
    ]);

    $this->registry->registerNamespace('App\\DummySettings');

    $this->registry->boot();

    expect($this->registry->settings)
        ->toHaveCount(1)
        ->toContain($fqcn)
        ->and($this->app->bound($fqcn));
});

test('warm boot loads from manifest', function () {
    $this->mockManifest->shouldReceive('present')->andReturnTrue();
    $this->mockManifest->shouldReceive('load')->andReturn([
        'group' => $fqcn = get_class(new class (new InMemoryRepository) extends Settings {}),
    ]);

    $this->registry->boot();

    expect($this->registry->settings)
        ->toHaveCount(1)
        ->toHaveKey('group')
        ->toContain($fqcn);
});

test('cold vs warm boot produce same registry', function () {
    // Warm boot
    $this->mockManifest->shouldReceive('present')->andReturnTrue();
    $this->mockManifest->shouldReceive('load')->andReturn([
        'group' => $fqcn = get_class(new class (new InMemoryRepository) extends Settings {}),
    ]);

    $this->registry->boot();

    $warmRegistry = $this->registry;

    $coldRegistry = new SettingsRegistry(
        $this->mockManifest,
        $this->mockFinder,
        $this->app,
    );

    $this->mockManifest->shouldReceive('present')->andReturnFalse();
    $this->mockFinder->shouldReceive('discover')->andReturn([
        $fqcn,
    ]);

    $coldRegistry->registerNamespace('App\\DummySettings');
    $coldRegistry->boot();

    expect($coldRegistry->settings)
        ->toEqual($warmRegistry->settings);
});

it('should not boot twice', function () {
    $this->mockManifest->shouldReceive('present')->once()->andReturnTrue();
    $this->mockManifest->shouldReceive('load')->once()->andReturn([
        'group' => $fqcn = get_class(new class (new InMemoryRepository) extends Settings {}),
    ]);

    expect($this->registry->booted)
        ->toBeFalse();

    $this->registry->boot();

    expect($this->registry->booted)
        ->toBeTrue();

    $this->registry->boot();

    expect($this->registry->settings)
        ->toHaveCount(1)
        ->toHaveKey('group')
        ->toContain($fqcn);
});
