<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;

/**
 * Abstract base class for application settings with automatic persistence.
 *
 * Provides automatic casting, change tracking, and group management.
 */
abstract class Settings
{
    /**
     * Cache for old settings values
     *
     * @var array
     */
    private array $oldSettings = [];

    /**
     * The initial values of the settings, and the current state of the
     *
     * @var array
     */
    private array $initialSettings = [];

    /**
     * Cache of public properties
     *
     * @var array
     */
    private array $cachedPublicPropertyNames = [];

    public function __construct(protected Repository $repository)
    {
        $this->setupGroup();

        $this->fill($this->repository->getAll());
    }

    /**
     * Fill the settings with the given data
     */
    private function fill(array $data): static
    {
        $properties = $this->getCachedPropertyNames();

        foreach ($properties as $name => $type) {
            if (array_key_exists($name, $data)) {
                $this->$name = filled($data[$name]) ? $this->castValue($data[$name], $type) : null;

                $this->initialSettings[$name] = $this->$name;
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
            if (array_key_exists($name, $this->initialSettings)) {
                if ($this->initialSettings[$name] !== $this->$name) {
                    $updatedSettings[$name] = $this->$name;
                }
            }
        }

        return $updatedSettings;
    }

    /**
     * Setup the settings' group
     */
    private function setupGroup(): void
    {
        try {
            $method = new \ReflectionMethod($this, 'group');

            if ($method->isStatic()) {
                $this->repository->setGroup($method->invoke(null));
            } else {
                throw new RuntimeException('The group method must be static.');
            }
        } catch (\ReflectionException) {
            // If method `Repository::group` does not exists, we use the default group
            $this->repository->setGroup('default');
        }
    }

    /**
     * Get the public property names and their types.
     *
     * @return array<string, \ReflectionNamedType|\ReflectionUnionType|\ReflectionIntersectionType|null>
     */
    private function getCachedPropertyNames(): array
    {
        if (empty($this->cachedPublicPropertyNames)) {
            $properties = (new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC);

            $this->cachedPublicPropertyNames = collect($properties)
                ->mapWithKeys(fn (\ReflectionProperty $property) => [$property->name => $property->getType()])
                ->all();
        }

        return $this->cachedPublicPropertyNames;
    }

    /**
     * Cast the given value
     *
     * @param mixed $value The value to cast
     * @param null|ReflectionIntersectionType|ReflectionNamedType|ReflectionUnionType $type The type to cast the value
     * @return mixed
     */
    private function castValue(mixed $value, null|\ReflectionIntersectionType|\ReflectionNamedType|\ReflectionUnionType $type): mixed
    {
        if ($type === null) {
            return $value;
        }

        if ($type instanceof \ReflectionIntersectionType) {
            throw new \InvalidArgumentException('Intersection types are not supported.');
        }

        if ($type instanceof \ReflectionUnionType) {
            $types = $type->getTypes();

            if (count($types) > 1) {
                throw new \InvalidArgumentException('Union types with more than one type are not supported.');
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
            default => throw new \InvalidArgumentException("Unsupported type casting: {$type->getName()}"),
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
}
