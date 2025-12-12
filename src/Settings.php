<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use Closure;
use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Base class for application settings with automatic persistence.
 *
 * Provides automatic casting, change tracking, and group management.
 *
 * @package Coyotito\LaravelSettings
 */
abstract class Settings
{
    /**
     * Cache for old settings values
     */
    final protected array $oldSettings = [];

    /**
     * The initial values of the settings, and the current state of the
     */
    final protected array $initialSettings = [];

    /**
     * Cache of public properties
     */
    final protected array $cachedPublicPropertyNames = [];

    /**
     * The default group name
     */
    public const string DEFAULT_GROUP = 'default';

    public function __construct(protected Repository $repository, public string $group = self::DEFAULT_GROUP)
    {
        $this->repository->group = $this->group;

        $properties = array_keys($this->getCachedPropertyNames());

        $this->fill(
            $this->repository->get($properties)
        );
    }

    /**
     * Get the value of a setting or multiple settings.
     *
     * @param string|array $key The setting key or an array of setting keys
     * @param mixed $default The default value to return if the setting is not found
     * @return mixed|array<string, mixed> The setting value or an array of setting values
     */
    public function get(string|array $key, mixed $default = null): mixed
    {
        if (is_string($key)) {
            return $this->{$key} ?? $default;
        }

        return collect($key)
            ->mapWithKeys(fn (string $k) => [$k => $this->get($k, $default)])
            ->all();
    }

    /**
     * Fill the settings with the given data
     */
    public function fill(array $data): static
    {
        return $this->massAssignment(
            $data,
            afterUpdatesSetting: function (mixed $value, string $setting) {
                if (! isset($this->initialSettings[$setting])) {
                    $this->initialSettings[$setting] = $this->$setting;
                }
            }
        );
    }

    /**
     * Update the given settings
     */
    public function update(array $settings): static
    {
        return $this->massAssignment($settings);
    }

    /**
     * @param ?Closure(mixed $value, string $setting): void $afterUpdatesSetting
     * @return $this
     */
    private function massAssignment(array $settings, ?Closure $afterUpdatesSetting = null): static
    {
        $properties = $this->getCachedPropertyNames();

        foreach ($settings as $name => $value) {
            if (array_key_exists($name, $properties)) {
                $this->$name = filled($value) ? $value : null;

                if ($afterUpdatesSetting) {
                    $afterUpdatesSetting(value: $this->$name, setting: $name);
                }
            }
        }

        return $this;
    }

    /**
     * Get the updated settings
     *
     * @return array<string, mixed>
     */
    private function getUpdated(): array
    {
        $properties = $this->getCachedPropertyNames();
        $updatedSettings = [];

        foreach (array_keys($properties) as $name) {
            if (array_key_exists($name, $this->initialSettings) && $this->initialSettings[$name] !== $this->$name) {
                $updatedSettings[$name] = $this->$name;
            }
        }

        return $updatedSettings;
    }

    /**
     * Get the public property names and their types.
     *
     * @return array<string, ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null>
     */
    private function getCachedPropertyNames(): array
    {
        if (blank($this->cachedPublicPropertyNames)) {
            $this->cachedPublicPropertyNames = $this->resolvePublicProperties();
        }

        return $this->cachedPublicPropertyNames;
    }

    /**
     * Resolve the public properties
     *
     * @return string[]
     */
    protected function resolvePublicProperties(): array
    {
        $properties = new ReflectionClass($this)->getProperties(ReflectionProperty::IS_PUBLIC);

        return collect($properties)
            ->map(fn (ReflectionProperty $property) => $property->name)
            ->reject(fn (string $property) => $property === 'group')
            ->all();
    }

    /**
     * Save the updated settings
     */
    final public function save(): void
    {
        $updatedSettings = $this->getUpdated();

        if (filled($updatedSettings)) {
            $this->repository->update($updatedSettings);

            foreach ($updatedSettings as $name => $value) {
                // save the old setting
                $this->oldSettings[$name] = $this->initialSettings[$name];

                // update the initial setting
                $this->initialSettings[$name] = $value;
            }
        }
    }

    /**
     * Get all the settings
     *
     * @return array<string, mixed>
     */
    final public function all(): array
    {
        return collect($this->initialSettings)
            ->merge($this->getUpdated())
            ->mapWithKeys(fn (mixed $payload, string $setting) => [$setting => $payload])
            ->all();
    }

    /**
     * Get the group name
     */
    public static function getGroup(): string
    {
        return Settings::DEFAULT_GROUP;
    }
}
