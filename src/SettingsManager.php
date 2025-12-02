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
use ReflectionNamedType;
use RuntimeException;

use function Coyotito\LaravelSettings\Helpers\psr4_namespace_to_path;
use function Illuminate\Filesystem\join_paths;

class SettingsManager
{
    protected Repository $repository {
        get {
            return $this->repository;
        }
        set {
            $this->repository = $value;
        }
    }
    protected array $resolvedSettings = [];

    protected array $resolvedNamespaces = [];

    public function __construct(protected ?Application $app = null)
    {
        $this->app ??= app();

        $this->repository = $this->app->make('settings.repository');
    }

    /**
     * Fake a settings class with the given data for testing purposes
     */
    public function fake(array $data = [], string $group = AbstractSettings::DEFAULT_GROUP): void
    {
        $this->clearResolvedSettings();
        $this->repository = new InMemoryRepository($group);

        $this->app->bind($this->getSettingsGroupKey($group), function () use ($data, $group) {
            $repository = $this->repository;

            return new #[AllowDynamicProperties] class ($repository, $group, $data) extends AbstractSettings {
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
     * Add a namespace and its corresponding path for Setting classes.
     *
     * @param string $namespace The namespace to register
     * @throws RuntimeException if the given namespace the path cannot be resolved
     */
    public function addNamespace(string $namespace): void
    {
        if ($this->resolveNamespacePath($namespace)) {
            return;
        }

        throw new RuntimeException("Could not resolve path for namespace: $namespace");
    }

    /**
     * Get the list of Setting classes.
     *
     * @return class-string<AbstractSettings>[]
     */
    public function getClasses(): array
    {
        $classes = [];

        foreach (array_keys($this->resolvedNamespaces) as $namespace) {
            $resolvedClasses = $this->resolveNamespaceSettings($namespace);

            if (blank($resolvedClasses)) {
                continue;
            }

            $classes = [...$resolvedClasses, ...$classes];
        }

        return $classes;
    }

    /**
     * Try to resolve the given namespace path
     */
    protected function resolveNamespacePath(string $namespace): ?string
    {
        $namespace = trim($namespace, '\\');

        if (array_key_exists($namespace, $this->resolvedNamespaces)) {
            return $this->resolvedNamespaces[$namespace];
        }

        if (blank($path = psr4_namespace_to_path($namespace))) {
            return null;
        }

        return $this->resolvedNamespaces[$namespace] = $path;
    }

    /**
     * Resolve the Settings classes in a given namespace.
     *
     * @return ?class-string<AbstractSettings>[]
     */
    protected function resolveNamespaceSettings(string $namespace): ?array
    {
        $settings = $this->getNamespaceSettings($namespace);

        if (filled($settings)) {
            collect($settings)
                ->reject(fn (string $settings) => $this->app->bound($settings))
                ->each(function (string $settings) {
                    $key = $this->getSettingsGroupKey($settings);

                    $this->ensureGroupIsUnique($key, $settings);

                    $this->app->scoped($key, function () use ($settings) {
                        $repository = $this->repository;

                        return new $settings($repository);
                    });
                });

            $this->resolvedSettings[$namespace] = array_merge(
                $this->resolvedSettings[$namespace],
                $settings
            );
        }

        return $settings ?: null;
    }

    /**
     * Get the classes inside the given namespace
     *
     * @return class-string<AbstractSettings>[]
     */
    protected function getNamespaceSettings(string $namespace): array
    {
        $path = $this->resolveNamespacePath($namespace);

        if ($path === null) {
            return [];
        }

        $files = File::glob(join_paths($path, '*.php')) ?: [];

        return collect($files)
            ->map(function (string $file) use ($namespace): string {
                $className = pathinfo($file, PATHINFO_FILENAME);

                return "$namespace\\$className";
            })
            ->reject(function (string $class): bool {
                return ! is_subclass_of($class, AbstractSettings::class);
            })
            ->all();
    }

    /**
     * Get the resolved settings class
     */
    protected function getResolvedSettingsClass(string $key): ?AbstractSettings
    {
        try {
            $settingsClass = $this->app->make($key);
        } catch (BindingResolutionException) {
            $settingsClass = null;
        }

        return $settingsClass;
    }

    /**
     * Get the setting group key for the given Setting class
     *
     * @param class-string<Setting>|string $class
     *
     * @throws RuntimeException if the setting group is already declared
     */
    public function getSettingsGroupKey(string $class): string
    {
        $group = is_subclass_of($class, AbstractSettings::class) ? $class::getGroup() : $class;

        return sprintf("%s::%s", AbstractSettings::class, $group);
    }

    public function ensureGroupIsUnique(string $key, string $group): void
    {
        if ($this->app->resolved($key)) {
            throw new RuntimeException("Cannot declare two settings with the same group [{$group}]");
        }
    }

    /**
     * Resolve the settings class with the given group if exists
     */
    public function resolveSettings(?string $group = null): ?AbstractSettings
    {
        $group ??= AbstractSettings::DEFAULT_GROUP;

        return $this->getResolvedSettingsClass(
            $this->getSettingsGroupKey($group)
        );
    }

    /**
     * Clear the resolved settings cache
     */
    public function clearResolvedSettings(): void
    {
        $this->resolvedSettings = [];
    }
}
