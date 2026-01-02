<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use Coyotito\LaravelSettings\Exceptions\GroupAlreadyRegisteredException;
use Coyotito\LaravelSettings\Exceptions\SettingsAlreadyRegisteredException;
use Coyotito\LaravelSettings\Finders\SettingsFinder;
use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;

use function Coyotito\LaravelSettings\Helpers\psr4_namespace_normalizer;

class SettingsRegistry
{
    public protected(set) bool $booted = false;

    /*** @var array<string, string> */
    public protected(set) array $settings = [];

    /** @var string[] */
    public protected(set) array $namespaces = [];

    public function __construct(protected SettingsManifest $manifest, protected SettingsFinder $finder, protected Application $app)
    {
        //
    }

    public function registerNamespace(string $namespace): self
    {
        $namespace = psr4_namespace_normalizer($namespace);

        if (in_array($namespace, $this->namespaces, true)) {
            return $this;
        }

        $this->namespaces[] = $namespace;

        return $this;
    }

    /**
     * @return $this
     * @throws GroupAlreadyRegisteredException if the settings group is already registered with other settings class
     * @throws SettingsAlreadyRegisteredException if the same settings class is already registered
     */
    public function registerSettings(string|array $settings): self
    {
        $settings = Arr::wrap($settings);

        foreach ($settings as $class) {
            $this->ensureSettingsIsNotAlreadyRegistered($class);

            $group = $this->resolveSettingsGroup($class);

            $this->settings[$group] = $class;
        }

        return $this;
    }

    /**
     * Bind the settings into the Laravel container
     *
     * @param class-string<Settings> $settings
     */
    public function bindSettings(string $settings): void
    {
        $this->resolveSettingsGroup($settings);

        $this->app->scoped($settings, static function ($app) use ($settings): Settings {
            /** @var Repository $repository */
            $repository = $app->make('settings.repository');

            return new $settings($repository);
        });
    }

    public function resolveSettings(string $term): ?Settings
    {
        $class = $this->findRegisteredClass($term);

        if ($class === null) {
            return null;
        }

        if (! $this->app->bound($class)) {
            return null;
        }

        try {
            return $this->app->make($class);
        } catch (BindingResolutionException) {
            return null;
        }
    }

    protected function findRegisteredClass(string $term): ?string
    {
        if (array_key_exists($term, $this->settings)) {
            return $this->settings[$term];
        }

        if (in_array($term, $this->settings, true)) {
            return $term;
        }

        return null;
    }

    /**
     * Get the `settings` group
     *
     * @param class-string<Settings> $settings
     */
    protected function resolveSettingsGroup(string $settings): string
    {
        return $settings::getGroup();
    }

    /**
     * Check if the given group/settings is not already registered
     *
     * @param class-string<Settings> $settings
     * @throws GroupAlreadyRegisteredException if the settings group is already registered with other settings class
     * @throws SettingsAlreadyRegisteredException if the same settings class is already registered
     */
    protected function ensureSettingsIsNotAlreadyRegistered(string $settings): void
    {
        $registeredSettings = Arr::get($this->settings, $group = $this->resolveSettingsGroup($settings));

        if (blank($registeredSettings)) {
            return;
        }

        if ($settings === $registeredSettings) {
            throw new SettingsAlreadyRegisteredException($settings);
        }

        throw new GroupAlreadyRegisteredException($settings, $registeredSettings, $group);
    }

    protected function register(): void
    {
        foreach ($this->namespaces as $namespace) {
            /** @var array $settings */
            $settings = $this->finder->discover($namespace);

            if (filled($settings)) {
                $this->registerSettings($settings);
            }
        }

        foreach ($this->settings as $settings) {
            $this->bindSettings($settings);
        }
    }

    protected function load(): void
    {
        $settings = $this->manifest->load();

        foreach ($settings as $group => $class) {
            $this->settings[$group] = $class;

            $this->bindSettings($class);
        }
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Check if the manifest is present if not, load settings in to the registry
        $this->manifest->present() ?
            $this->load() :
            $this->register();

        $this->booted = true;
    }
}
