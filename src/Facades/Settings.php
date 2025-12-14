<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Facades;

use Coyotito\LaravelSettings\SettingsService as SettingsService;
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
    public static function fake(array $data = [], string $group = \Coyotito\LaravelSettings\Settings::DEFAULT_GROUP): DynamicSettings
    {
        /**
         * @var \Coyotito\LaravelSettings\SettingsManager $manager
         */
        $manager = tap(
            static::$app->make('settings.manager'),
            fn ($manager) => $manager->clearRegisteredSettingsClasses()
        );

        static::$app->forgetInstance(Repository::class);
        static::$app->scoped(
            Repository::class,
            fn () => tap(
                new InMemoryRepository($group),
                fn ($repo) => $data && $repo->insert($data)
            )
        );

        $manager->registerSettingsClass(DynamicSettings::class, $group);

        /** @var DynamicSettings $settings */
        $settings = $manager->resolveSettings($group);

        return $settings;
    }

    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor(): string
    {
        return 'settings.service';
    }
}
