<?php

namespace Coyotito\LaravelSettings\Settings;

use Coyotito\LaravelSettings\Settings;
use Coyotito\LaravelSettings\Repositories\Contracts\Repository;

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
            ->map(fn (mixed $value, string $property) => $property)->toArray();
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
