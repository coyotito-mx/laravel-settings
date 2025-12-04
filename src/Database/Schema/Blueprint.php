<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Database\Schema;

use Coyotito\LaravelSettings\Repositories\Contracts\Repository;

/**
 * Blueprint class
 *
 * Used to define settings to be added or removed
 * from the settings repository
 *
 * @package Coyotito\LaravelSettings
 */
final class Blueprint
{
    /**
     * All the settings to add
     */
    protected array $settingsToAdd = [];

    /**
     * All the settings to remove
     */
    protected array $settingsToDelete = [];

    public function __construct(protected Repository $repository)
    {
        //
    }

    /**
     * Add setting
     */
    public function add(string $name, mixed $value = null): self
    {
        $this->settingsToAdd[\Str::snake($name)] = $value;

        return $this;
    }

    /**
     * Remove setting
     */
    public function remove(string $name): self
    {
        $this->settingsToDelete[] = $name;

        return $this;
    }

    /**
     * Add settings
     */
    protected function addSettings(array $settings): void
    {
        $this->repository->insert($settings);
    }

    /**
     * Delete settings
     */
    protected function deleteSettings(array $settings): void
    {
        $this->repository->delete($settings);
    }

    /**
     * Will be call after the object is deleted/destroy
     */
    public function __destruct()
    {
        $this->deleteSettings($this->settingsToDelete);

        $this->addSettings($this->settingsToAdd);
    }
}
