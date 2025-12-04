<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use Illuminate\Support\Arr;
use RuntimeException;

/**
 * Settings service
 *
 * @package Coyotito\LaravelSettings
 */
class SettingsService
{
    public function __construct(protected ?SettingsManager $manager, protected string $group = Settings::DEFAULT_GROUP)
    {
        //
    }

    /**
     * Get settings
     *
     * if `$key` is string and `$default` an `array`, the `$key` will now represent
     * the group from where you want to get the settings
     */
    public function get(string|array $key, mixed $default = null): mixed
    {
        if (is_null($default)) {
            return $this->settingsManager()->get($key);
        }

        return $this->settingsManager()->get($key, $default);
    }

    /**
     * Update settings
     *
     * if `$key` is string and `$default` an `array`, the `$key` will now represent
     * the group from where you want to update the settings
     */
    public function set(string|array $values, mixed $default = null): self
    {
        if (is_string($values)) {
            $settings = [$values => $default];
        } else {
            $settings = Arr::mapWithKeys(
                $values,
                function (mixed $value, int|string $key) use ($default) {
                    if (is_int($key)) {
                        [$key, $value] = [$value, $default];
                    }

                    return [
                        $key => $value,
                    ];
                }
            );
        }

        $this->settingsManager()->fill($settings)->save();

        return $this;
    }

    /**
     * Specify the settings group
     */
    public function group(string $group): self
    {
        return new self($this->manager, $group);
    }

    /**
     * Manage the settings based on the specified group
     */
    protected function settingsManager(): Settings
    {
        $settings = $this->manager->resolveSettings($this->group);

        if (blank($settings)) {
            throw new RuntimeException("Settings group [$this->group] not found.");
        }

        return $settings;
    }
}
