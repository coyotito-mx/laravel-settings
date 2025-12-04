<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Facades;

use Coyotito\LaravelSettings\Settings as SettingsService;
use Coyotito\LaravelSettings\AbstractSettings;
use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use Coyotito\LaravelSettings\Repositories\InMemoryRepository;
use Coyotito\LaravelSettings\Settings\DynamicSettings;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Settings service.
 *
 * @mixin SettingsService
 *
 * @package Coyotito\LaravelSettings
 */
class Settings extends Facade
{
    /**
     * Fake a settings class with the given data for testing purposes
     */
    public static function fake(array $data = [], string $group = AbstractSettings::DEFAULT_GROUP): void
    {
        SettingsManager::clearRegisteredSettings();

        static::$app->forgetInstance(Repository::class);
        static::$app->scoped(
            Repository::class,
            fn () => tap(
                new InMemoryRepository($group),
                fn ($repo) => $data && $repo->insert($data)
            )
        );

        SettingsManager::registerSettingsClass(DynamicSettings::class, $group);
    }

    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor(): string
    {
        return 'settings.service';
    }
}
