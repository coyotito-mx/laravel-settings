<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use AllowDynamicProperties;
use Coyotito\LaravelSettings\Models\Setting;
use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use Coyotito\LaravelSettings\Repositories\InMemoryRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use ReflectionNamedType;

use RuntimeException;
use function Coyotito\LaravelSettings\Helpers\psr4_namespace_to_path;
use function Illuminate\Filesystem\join_paths;

class LaravelSettingsManager
{
    protected array $settingsFolders = [];

    protected static array $resolvedSettings = [];

    public function __construct(protected(set) ?Application $app = null)
    {
        $this->app ??= app();
    }

    public function setApplication(Application $app): void
    {
        $this->app = $app;
    }

    /**
     * Fake a settings class with the given data for testing purposes
     */
    public function fake(array $data = [], string $group = Settings::DEFAULT_GROUP): void
    {
        $this->clearResolvedSettings();
        $this->swapRepository(fn () => new InMemoryRepository($group));

        $this->app->bind(Settings::class."::$group", function () use ($data, $group) {
            $repository = $this->app->make('settings.repository');

            return new #[AllowDynamicProperties] class ($repository, $group, $data) extends Settings {
                public function __construct(protected Repository $repository, protected string $group, protected array $dynamicSettings)
                {
                    $this->setDynamicSettings($this->dynamicSettings);

                    parent::__construct($repository, $group);
                }

                protected function resolvePublicProperties(): array
                {
                    return collect(get_object_vars($this))
                        ->only(array_keys($this->dynamicSettings))
                        ->mapWithKeys(function (mixed $value, string $property): array {
                            $type = new class ($value) extends ReflectionNamedType {
                                private const array BUILTIN_TYPES = [
                                    'integer',
                                    'double',
                                    'string',
                                    'boolean',
                                    'array',
                                    'object',
                                    'callable',
                                    'iterable',
                                    'null',
                                    'void',
                                    'never',
                                    'mixed',
                                    'false',
                                    'true',
                                ];

                                public function __construct(protected mixed $value)
                                {
                                    //
                                }

                                public function getName(): string
                                {
                                    return gettype($this->value);
                                }

                                public function isBuiltin(): bool
                                {
                                    return self::BUILTIN_TYPES[$this->getName()] ?? false;
                                }

                                public function allowsNull(): bool
                                {
                                    return true;
                                }

                                public function __toString(): string
                                {
                                    return $this->getName();
                                }
                            };

                            return [$property => $type];
                        })->toArray();
                }

                private function setDynamicSettings(array $settings): void
                {
                    $this->repository->insert(
                        $this->prepareSettings($settings)
                    );

                    foreach ($settings as $key => $value) {
                        $this->set($key, $value);
                    }
                }

                private function set(string $key, $value): void
                {
                    $this->$key = $value;
                }

                private function prepareSettings(array $settings): array
                {
                    return array_map(fn ($payload) => $payload, $settings);
                }
            };
        });
    }

    /**
     * Swap the settings repository implementation
     */
    public function swapRepository(callable $factory): void
    {
        $this->app->extend('settings.repository', $factory);
    }

    /**
     * Add a namespace and its corresponding path for Setting classes.
     */
    public function addNamespace(string $namespace, ?string $path = null): void
    {
        $path = $path ?? psr4_namespace_to_path($namespace);

        if (blank($path)) {
            throw new InvalidArgumentException("Could not resolve path for namespace: $namespace");
        }

        $this->settingsFolders[trim($namespace, '\\')] = $path;
    }

    /**
     * Get the list of Setting classes.
     *
     * @return class-string<Setting>[]
     */
    public function getClasses(): array
    {
        $classes = [];

        if (blank(config('settings.classes'))) {
            return $classes;
        }

        foreach (array_keys($this->settingsFolders) as $namespace) {
            $resolvedClasses = $this->resolveNamespaceClasses($namespace);

            if (blank($resolvedClasses)) {
                continue;
            }

            $classes = [...$resolvedClasses, ...$classes];
        }

        return $classes;
    }

    /**
     * Resolve the Setting classes in a given namespace.
     *
     * @return ?class-string<Setting>[]
     */
    protected function resolveNamespaceClasses(string $namespace): ?array
    {
        $directory = $this->settingsFolders[$namespace];

        $files = File::glob(
            join_paths($directory, '*.php')
        );

        if (empty($files)) {
            return null;
        }

        $classes = Arr::map($files, function (string $file) use ($namespace): string {
            $className = pathinfo($file, PATHINFO_FILENAME);

            return "$namespace\\$className";
        });

        return Arr::reject($classes, fn (string $class): bool => ! is_subclass_of($class, Settings::class));
    }

    /**
     * Get the resolved settings class
     */
    protected function getResolvedSettingsClass(string $key): ?Settings
    {
        if ($settingsClass = static::$resolvedSettings[$key] ?? null) {
            return $settingsClass;
        }

        try {
            $settingsClass = $this->app->make($key);

            static::$resolvedSettings[$key] = $settingsClass;
        } catch (BindingResolutionException) {
            $settingsClass = null;
        }

        return $settingsClass;
    }

    /**
     * Get the setting group key for the given Setting class
     *
     * @param class-string<Setting>|string $class
     * @return string
     *
     * @throws RuntimeException if the setting group is already declared
     */
    public function getSettingsGroupKey(string $class): string
    {
        $group = is_subclass_of($class, Settings::class) ? $class::getGroup() : $class;

        return sprintf("%s::%s", Settings::class, $group);
    }

    public function ensureGroupIsUnique(string $key, string $group): void
    {
        if ($this->app->resolved($key)) {
            throw new RuntimeException("Cannot declare two settings with the same group [{$group}]");
        }
    }

    /**
     * Clear the resolved settings cache
     */
    public function clearResolvedSettings(): void
    {
        static::$resolvedSettings = [];
    }

    /**
     * Resolve the settings class with the given group if exists
     */
    public function resolveSettings(?string $group = null): ?Settings
    {
        $group ??= Settings::DEFAULT_GROUP;

        return $this->getResolvedSettingsClass(
            $this->getSettingsGroupKey($group)
        );
    }
}
