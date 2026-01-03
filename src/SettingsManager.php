<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use Coyotito\LaravelSettings\Exceptions\GroupAlreadyRegisteredException;
use Coyotito\LaravelSettings\Exceptions\SettingsAlreadyRegisteredException;

use function Coyotito\LaravelSettings\Helpers\psr4_namespace_normalizer;
use function Coyotito\LaravelSettings\Helpers\psr4_namespace_to_path;

/**
 * Manages the registration and resolution of settings classes.
 *
 * @package Coyotito\LaravelSettings
 */
class SettingsManager
{
    public function __construct(protected SettingsRegistry $registry)
    {
        //
    }

    /**
     * Add a namespace and its corresponding path for Setting classes.
     */
    public function addNamespace(string $namespace): void
    {
        $this->registry->registerNamespace($namespace);
    }

    /**
     * Register a Setting class in the container.
     *
     * @throws GroupAlreadyRegisteredException
     * @throws SettingsAlreadyRegisteredException
     */
    public function registerSettingsClass(string|array $settings): void
    {
        $this->registry->registerSettings($settings);
    }

    /**
     * Resolve the settings instance
     */
    public function resolveSettings(string $term): ?Settings
    {
        return $this->registry->resolveSettings($term);
    }

    /**
     * Clear the resolved settings cache
     */
    public function clearRegisteredSettingsClasses(): void
    {
        $this->registry->clearRegisteredSettings();
    }

    public function loadSettings(): void
    {
        $this->registry->boot();
    }

    public function normalizeNamespace(string $namespace): string
    {
        return psr4_namespace_normalizer($namespace);
    }

    public function resolveNamespacePath(string $namespace): ?string
    {
        return psr4_namespace_to_path($namespace);
    }
}
