<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Repositories\Contracts;

use Coyotito\LaravelSettings\Models\Exceptions\LockedSettingException;

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
     *
     * @return ($settings is string ? mixed : array<string, mixed>)
     */
    public function get(string|array $settings, mixed $default = null): mixed;

    /**
     * Get all the settings
     *
     * @return array<string, mixed>
     */
    public function getAll(): array;

    /**
     * Update one or more settings
     *
     * @throws LockedSettingException if trying to update at least one locked setting
     */
    public function update(string|array $setting, mixed $value = null): void;

    /**
     * Insert one or more setting
     */
    public function insert(string|array $setting, mixed $value = null): void;

    /**
     * Insert or update one or more settings
     */
    public function upsert(string|array $setting, mixed $value = null): void;

    /**
     * Delete one or more settings
     *
     * @return int The count of deleted settings
     *
     * @throws LockedSettingException if trying to update at least one locked setting
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
