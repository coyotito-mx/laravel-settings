<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Repositories\Contracts;

/**
 *
 * Handles the processing of settings from a group of settings
 *
 * @template TSettingParam of array<string, mixed|null>
 *
 * @package Coyotito\SettingsManager
 */
interface Repository
{
    /**
     * The group of settings being handled
     */
    public string $group {
        get;
        set;
    }

    /**
     * Get one or more settings
     *
     * @return ($setting is string ? mixed|null : array<string, mixed|null>)
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
     *
     * If the `$setting` is a `string`, this will be the name of the setting to update using the provided, or not, the
     * argument `$value`. When `$setting` is an `array`, and this is a list, all the settings provided will be updated using
     * the argument `$value` as for every single setting provided in the list. Otherwise, the assoc `$setting` will contain every value
     * to update for each setting.
     *
     * @param string|string[]|TSettingParam[] $setting The settings to update|create
     * @param mixed $value The value to update|create
     */
    public function update(string|array $setting, mixed $value = null): void;

    /**
     * Insert one or more setting
     *
     * If the `$setting` is a `string`, this will be the name of the setting to create using the provided, or not, the
     * argument `$value`. When `$setting` is an `array`, and this is a list, all the settings provided will be created using
     * the argument `$value` as for every single setting provided in the list. Otherwise, the assoc `$setting` will contain every value
     * to create for each setting.
     *
     * @param string|string[]|TSettingParam[] $setting The settings to update|create
     * @param mixed $value The value to update|create
     */
    public function insert(string|array $setting, mixed $value = null): void;

    /**
     * Insert or update one or more settings
     *
     * If the `$setting` is a `string`, this will be the name of the setting to create|update using the provided, or not, the
     * argument `$value`. When `$setting` is an `array`, and this is a list, all the settings provided will be created|updated using
     * the argument `$value` as for every single setting provided in the list. Otherwise, the assoc `$setting` will contain every value
     * to create|update for each setting.
     *
     * @param string|string[]|TSettingParam[] $setting The settings to update|create
     * @param mixed $value The value to update|create
     */
    public function upsert(string|array $setting, mixed $value = null): void;

    /**
     * Delete one or more settings
     *
     * @param string|string[] $setting
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
