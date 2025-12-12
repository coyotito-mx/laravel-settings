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
     * Registered namespaces and their corresponding Setting classes.
     *
     * @var array <string, class-string<Settings>[]>
     */
    protected array $namespaces = [];

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
        $namespace = psr4_namespace_normalizer($namespace);

        $settingsClasses = $this->resolveSettingsClassesFromNamespace($namespace);

        if ($settingsClasses === null) {
            return;
        }

        $this->namespaces[$namespace] = array_unique(
            array_merge($settingsClasses, $this->namespaces[$namespace] ?? [])
        );

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

            return "$namespace\\$className";
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

        if (! $this->app->has($group)) {
            return;
        }

        $existingSettings = $this->registeredSettings[$group];

        throw new InvalidArgumentException(sprintf(
            'Settings group "%s" is already registered by class "%s". Cannot register class "%s" with the same group.',
            $existingSettings::getGroup(),
            class_basename($settings),
            $settings,
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
     * Clear all registered namespaces
     */
    public function clearRegisteredNamespaces(): void
    {
        foreach (array_keys($this->namespaces) as $namespace) {
            unset($this->namespaces[$namespace]);
        }
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

        $this->app->forgetInstance($settings);
    }

    /**
     * Clear the resolved settings cache
     */
    public function clearRegisteredSettingsClasses(): void
    {
        $this->clearRegisteredNamespaces();

        foreach ($this->registeredSettings as $settings) {
            $this->clearRegisteredSettingsClass($settings);
        }
    }
}
