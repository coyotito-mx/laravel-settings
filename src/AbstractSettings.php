<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Abstract base class for application settings with automatic persistence.
 *
 * Provides automatic casting, change tracking, and group management.
 */
abstract class AbstractSettings
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

    public function __construct(protected Repository $repository, protected string $group = self::DEFAULT_GROUP)
    {
        $this->repository->group = $this->group;

        $properties = array_keys($this->getCachedPropertyNames());

        $this->fill(
            $this->repository->get($properties)
        );
    }

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
        $properties = $this->getCachedPropertyNames();

        foreach ($properties as $name => $type) {
            if (array_key_exists($name, $data)) {
                $this->$name = filled($data[$name]) ? $this->castValue($data[$name], $type) : null;

                if (! isset($this->initialSettings[$name])) {
                    $this->initialSettings[$name] = $this->$name;
                }
            }
        }

        return $this;
    }

    /**
     * Get the updated settings
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

    protected function resolvePublicProperties(): array
    {
        $properties = new ReflectionClass($this)->getProperties(ReflectionProperty::IS_PUBLIC);

        return collect($properties)
            ->mapWithKeys(fn (ReflectionProperty $property) => [$property->name => $property->getType()])
            ->all();
    }

    /**
     * Cast the given value
     *
     * @param mixed $value The value to cast
     * @param null|ReflectionIntersectionType|ReflectionNamedType|ReflectionUnionType $type The type to cast the value
     */
    private function castValue(mixed $value, null|\ReflectionIntersectionType|\ReflectionNamedType|\ReflectionUnionType $type): mixed
    {
        if ($type === null) {
            return $value;
        }

        if ($type instanceof ReflectionIntersectionType) {
            throw new InvalidArgumentException('Intersection types are not supported.');
        }

        if ($type instanceof ReflectionUnionType) {
            $types = $type->getTypes();

            if (count($types) > 1) {
                throw new InvalidArgumentException('Union types with more than one type are not supported.');
            }

            $type = $types[0];
        }

        if ($type->allowsNull() && ($value === 'null' || $value === '')) {
            return null;
        }

        return match ($type->getName()) {
            'array' => (array) $value,
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'string' => (string) $value,
            default => throw new InvalidArgumentException("Unsupported type casting: {$type->getName()}"),
        };
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
     */
    final public function all(): array
    {
        return collect($this->initialSettings)
            ->merge($this->getUpdated())
            ->mapWithKeys(fn (mixed $payload, string $setting) => [$setting => $payload])
            ->all();
    }

    public static function getGroup(): string
    {
        return AbstractSettings::DEFAULT_GROUP;
    }
}
