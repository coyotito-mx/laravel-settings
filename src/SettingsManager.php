<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;
use InvalidArgumentException;

use function Coyotito\LaravelSettings\Helpers\psr4_namespace_normalizer;
use function Coyotito\LaravelSettings\Helpers\psr4_namespace_to_path;
use function Illuminate\Filesystem\join_paths;

/**
 * Manages the registration and resolution of settings classes.
 *
 * @package Coyotito\LaravelSettings
 */
class SettingsManager
{
    /**
     * Registered Setting classes by their group names.
     *
     * @var array<string, class-string<Settings>>
     */
    protected array $registeredSettings = [];

    public function __construct(protected(set) Application $app)
    {
        //
    }

    /**
     * Add a namespace and its corresponding path for Setting classes.
     */
    public function addNamespace(string $namespace): void
    {
        $namespace = $this->normalizeNamespace($namespace);

        $settingsClasses = $this->resolveSettingsClassesFromNamespace($namespace);

        if ($settingsClasses === null) {
            return;
        }

        foreach ($settingsClasses as $settings) {
            $this->registerSettingsClass($settings);
        }
    }

    /**
     * Resolve the Setting classes in a given namespace.
     *
     * @return ?class-string<Settings>[]
     * @throws InvalidArgumentException if the given path is an empty string
     */
    protected function resolveSettingsClassesFromNamespace(string $namespace): ?array
    {
        $path = $this->resolveNamespacePath($namespace);

        $files = File::glob(join_paths($path, '*.php'));

        if (blank($files)) {
            return null;
        }

        $classes = Arr::map($files, function (string $file) use ($namespace): string {
            $className = pathinfo($file, PATHINFO_FILENAME);

            return $this->normalizeNamespace($namespace).$className;
        });

        return Arr::reject($classes, fn (string $class): bool => ! is_subclass_of($class, Settings::class));
    }

    /**
     * Get the path resolved from the given namespace
     */
    public function resolveNamespacePath(string $namespace): ?string
    {
        return psr4_namespace_to_path($namespace);
    }

    /**
     * Register a Setting class in the container.
     *
     * @param class-string<Settings> $settings
     */
    public function registerSettingsClass(string $settings, ?string $group = null): void
    {
        $this->bindSettingsClass($settings, $group);

        if (method_exists($settings, 'preload') && $settings::preload()) {
            $this->preloadSettingsClass($settings);
        }
    }

    /**
     * Bind the settings class to the container
     */
    protected function bindSettingsClass(string $settings, ?string $group = null): void
    {
        if (blank($group)) {
            $group = $this->resolveGroupName($settings);
        }

        $this->ensureUniqueGroupRegistration($settings);

        $this->registeredSettings[$group] = $settings;

        $this->app->scoped($settings, static function ($app) use ($settings, $group): Settings {
            $repository = $app->make('settings.repository');

            /** @var Settings $instance */
            $instance = new $settings($repository);

            if (filled($instance)) {
                $instance->group = $group;
            }

            return $instance;
        });

        $this->app->alias($settings, $group);
    }

    /**
     * Preload the settings class if needed
     */
    protected function preloadSettingsClass(string|Settings $settings): void
    {
        $this->app->make($settings);
    }

    /**
     * Resolve the settings instance from the given group
     */
    public function resolveSettings(string $group): ?Settings
    {
        $settings = $this->registeredSettings[$group] ?? null;

        if (blank($settings)) {
            return null;
        }

        return $this->app->make($settings);
    }

    /**
     * Check if the settings group is already registered
     */
    protected function ensureUniqueGroupRegistration(string $settings): void
    {
        $group = $this->resolveGroupName($settings);

        if (! ($this->registeredSettings[$group] ?? null)) {
            return;
        }

        $existingSettings = $this->registeredSettings[$group];

        if ($existingSettings === $settings) {
            throw new InvalidArgumentException(sprintf(
                "Settings group '%s' already registered by class '%s'",
                $settings::getGroup(),
                class_basename($settings)
            ));
        }

        throw new InvalidArgumentException(sprintf(
            "Cannot register class '%s', '%s' already registered by class '%s'",
            class_basename($settings),
            $existingSettings::getGroup(),
            class_basename($existingSettings),
        ));
    }

    /**
     * Resolve the settings group key for the given settings class
     *
     * @param class-string<Settings> $settings
     */
    public function resolveGroupName(string $settings): string
    {
        return $settings::getGroup();
    }

    /**
     * Clear a registered settings class
     *
     * @param class-string<Settings> $settings
     */
    public function clearRegisteredSettingsClass(string $settings): void
    {
        $group = $this->resolveGroupName($settings);

        if (! isset($this->registeredSettings[$group])) {
            return;
        }

        unset($this->registeredSettings[$group]);
        tap($this->app, fn ($app) => $app->offsetUnset($settings))->offsetUnset($group);
    }

    /**
     * Clear the resolved settings cache
     */
    public function clearRegisteredSettingsClasses(): void
    {
        foreach ($this->registeredSettings as $settings) {
            $this->clearRegisteredSettingsClass($settings);
        }
    }

    public function normalizeNamespace(string $namespace): string
    {
        return psr4_namespace_normalizer($namespace);
    }
}
