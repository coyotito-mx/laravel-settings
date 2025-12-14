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
final class DynamicSettings extends Settings
{
    protected array $dynamicProperties = [];

    public function __construct(protected Repository $repository)
    {
        $this->setDynamicSettings($this->repository->getAll());

        parent::__construct($repository, $this->repository->group);
    }

    protected function resolvePublicProperties(): array
    {
        return collect(get_object_vars($this))
            ->only(array_keys($this->dynamicProperties))
            ->keys()
            ->toArray();
    }

    private function setDynamicSettings(array $settings): void
    {
        $settings = array_is_list($settings) ? array_fill_keys($settings, null) : $settings;

        foreach ($settings as $name => $value) {
            $this->$name = $value;
        }

        $this->dynamicProperties = $settings;
    }

    public function regenerate(): self
    {
        return new self($this->repository);
    }
}
