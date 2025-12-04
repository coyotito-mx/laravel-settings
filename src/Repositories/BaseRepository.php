<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Repositories;

use Closure;
use Coyotito\LaravelSettings\Casters\Contracts\PrepareValue;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Base repository for settings storage.
 *
 * @template-covariant TPrepareValue of PrepareValue
 * @template TNormalizedSetting of array{name: string, payload: TPrepareValue}
 *
 * @package Coyotito\LaravelSettings
 */
abstract class BaseRepository implements Contracts\Repository
{
    public function __construct(public string $group)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function get(string|array $setting, mixed $default = null): mixed
    {
        $normalizedSettings = $this->normalizeSettings($setting, $default);
        $settings = $this->getSettings($normalizedSettings->keys()->all());

        $result = $normalizedSettings->mapWithKeys(function (mixed $defaultValue, string $name) use ($settings) {
            /** @var TNormalizedSetting $setting */
            $setting = $settings->get($name, $defaultValue);

            return [$name => $setting['payload']->restore()];
        })->all();

        if (is_string($setting)) {
            return $result[$setting] ?? $default;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(): array
    {
        return $this->getSettings()
            ->pluck('payload', 'name')
            ->map(fn (PrepareValue $payload) => $payload->restore())
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function update(string|array $setting, mixed $value = null): void
    {
        $settings = $this
            ->normalizeSettings($setting, $value, function (array $settings) {
                return $this->getSettings($settings)->keys()->all();
            });

        if ($settings->isEmpty()) {
            return;
        }

        $this->updateMany($settings->all());
    }

    /**
     * {@inheritDoc}
     */
    public function insert(string|array $setting, mixed $value = null): void
    {
        $settings = $this->normalizeSettings($setting, $value, function (array $settings) {
            $existingSettings = $this->getSettings($settings)->keys()->all();

            return array_diff($settings, $existingSettings);
        });

        if ($settings->isEmpty()) {
            return;
        }

        $this->insertMany($settings->all());
    }

    /**
     * {@inheritDoc}
     */
    public function upsert(string|array $setting, mixed $value = null): void
    {
        $settings = $this->normalizeSettings($setting, $value);

        $this->upsertMany($settings->all());
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string|array $setting): void
    {
        $settings = $this->normalizeSettings($setting, only: function (array $settings): array {
            return $this->getSettings($settings)->keys()->all();
        });

        $this->deleteMany($settings->keys()->all());
    }

    /**
     * Normalize the settings input
     *
     * This function will normalize the settings input into a collection of setting
     * names and their values, if provided, or use the default value otherwise.
     *
     * @param string|array $setting The setting name or an array of settings
     * @param mixed $default The default value to use if a setting value is not provided
     * @param null|Collection|array|Closure(string[] $settings): string[] $only An optional filter to include only specific settings
     *
     * @returns Collection<string, TNormalizedSetting>
     */
    protected function normalizeSettings(string|array $setting, mixed $default = null, null|Collection|array|Closure $only = null): Collection
    {
        if (is_string($setting)) {
            $settings = collect([$setting => $default]);
        } else {
            $settings = collect(Arr::isAssoc($setting) ? $setting : array_fill_keys($setting, $default));
        }

        if (is_array($only)) {
            $settings = $settings->only($only);
        } elseif ($only instanceof Closure) {
            $settingsNames = $settings->keys()->all();

            $settings = $settings->only(
                $only(settings: $settingsNames)
            );
        }

        return $settings->mapWithKeys(fn (mixed $value, string $key) => [
            $key => [
                'name' => $key,
                'payload' => $this->castValue($value),
            ],
        ]);
    }

    /**
     * Cast the value from storage
     *
     * @returns TPrepareValue
     */
    protected function castValue(mixed $value): PrepareValue
    {
        return new class ($value) implements PrepareValue {
            public function __construct(protected mixed $value)
            {
                //
            }

            public function transform(): mixed
            {
                return $this->value;
            }

            public function restore(): mixed
            {
                return $this->value;
            }

            public function getRawValue(): mixed
            {
                return $this->value;
            }
        };
    }

    /**
     * Get all the settings from the storage
     *
     * If no settings names are provided, all settings will be retrieved from the storage.
     *
     * @param ?string[] $settings The names of the settings to retrieve
     */
    abstract protected function getSettings(?array $settings = null): Collection;

    /**
     * Update many settings in the storage
     *
     * This function will update multiple settings in the storage based on the provided
     * pair of setting names and their values, and only provide the data to update
     * for already present settings.
     *
     * @returns array<string, TNormalizedSetting>
     */
    abstract protected function updateMany(array $settings): void;

    /**
     * Insert many settings in the storage
     *
     * This function will insert multiple settings in the storage based on the provided
     * pair of setting names and their values, and only provide the data to insert
     * non-present settings.
     *
     * @returns array<string, TNormalizedSetting>
     */
    abstract protected function insertMany(array $settings): void;

    /**
     * Upsert many settings in the storage
     *
     * This function will upsert multiple settings in the storage based on the provided
     * pair of setting names and their values.
     *
     * @returns array<string, TNormalizedSetting>
     */
    abstract protected function upsertMany(array $settings): void;

    /**
     * Delete many settings from the storage
     *
     * This function will delete multiple settings from the storage based on the provided
     * setting names.
     *
     * @param string[] $settings The names of the settings to delete
     */
    abstract protected function deleteMany(array $settings): void;
}
