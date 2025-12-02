<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Repositories\Contracts;

/**
 *
 * Handles the processing of settings from a group of settings
 *
 * @package Coyotito\SettingsManager
 */
interface Repository
{
    public string $group {
        get;
        set;
    }

    /**
     * Get one or more settings
     *
     * @return ($setting is string ? mixed : array<string, mixed>)
     */
    public function get(string|array $setting, mixed $default = null): mixed;

    /**
     * Get all the settings
     *
     * @return array<string, mixed> All the settings in the group
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
     * Insert or update one or more settings
     */
    public function upsert(string|array $setting, mixed $value = null): void;

    /**
     * Delete one or more settings
     */
    public function delete(string|array $setting): void;

    /**
     * Drop all the settings
     */
    public function drop(): void;

    /**
     * Rename a settings' group name
     */
    public function renameGroup(string $newGroup): void;
}
