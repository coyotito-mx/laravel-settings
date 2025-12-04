<?php

namespace Coyotito\LaravelSettings\Settings;

use Coyotito\LaravelSettings\Settings;
use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use ReflectionNamedType;

/**
 * A settings class that dynamically creates properties based on the settings stored in the repository.
 *
 * @internal
 *
 * @package Coyotito\LaravelSettings
 */
class DynamicSettings extends Settings
{
    protected array $dynamicProperties = [];

    public function __construct(protected Repository $repository)
    {
        $this->setDynamicSettings(
            $this->dynamicProperties = $this->repository->getAll()
        );

        parent::__construct($repository, $this->repository->group);
    }

    protected function resolvePublicProperties(): array
    {
        return collect(get_object_vars($this))
            ->only(array_keys($this->dynamicProperties))
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
}
