<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Repositories\Contracts;

/**
 *
 * Handles the processing of settings from a group of settings
 *
 * @package Coyotito\LaravelSettings
 */
interface Repository
{
    /**
     * Get a setting value
     */
    public function get(string $name, mixed $default = null): mixed;

    /**
     * Get all the settings
     */
    public function getAll(): array;

    /**
     * Set a value to a settings
     */
    public function update(string $name, mixed $value): void;

    /**
     * Update many setting values
     */
    public function updateMany(array $settings): void;

    /**
     * Insert many setting values
     */
    public function insertMany(array $settings): void;

    /**
     * Delete a setting
     */
    public function delete(string $name): void;

    /**
     * Delete settings
     */
    public function deleteMany(array $names): int;

    /**
     * Get the setting's group
     */
    public function getGroup(): string;

    /**
     * Set the settings group
     */
    public function setGroup(string $group): void;

    /**
     * Delete all the settings
     */
    public function deleteAll(): void;

    /**
     * Rename a settings' group name
     */
    public function renameGroup($newGroup): void;
}
