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
     * Get one or more settings
     */
    public function get(string|array $setting, mixed $default = null): mixed;

    /**
     * Get all the settings
     */
    public function getAll(): array;

    /**
     * Update one or more settings
     */
    public function update(string|array $setting, mixed $value = null): void;

    /**
     * Insert one or more setting
     */
    public function insert(string|array $setting, mixed $value = null): void;

    /**
     * Delete one or more settings
     */
    public function delete(string|array $setting): int;

    /**
     * Drop all the settings
     */
    public function drop(): void;

    /**
     * Get the setting's group
     */
    public function group(): string;

    /**
     * Set the settings group
     */
    public function setGroup(string $group): void;

    /**
     * Rename a settings' group name
     */
    public function renameGroup(string $newGroup): void;
}
