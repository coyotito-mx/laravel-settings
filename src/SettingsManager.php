<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use Illuminate\Support\Arr;
use RuntimeException;

final class SettingsManager
{
    public function __construct(protected ?LaravelSettingsManager $manager = null, protected string $group = AbstractSettings::DEFAULT_GROUP)
    {
        $this->manager ??= app(LaravelSettingsManager::class);
    }

    public function get(string|array $key, mixed $default = null): mixed
    {
        if (is_null($default)) {
            return $this->settings()->get($key);
        }

        return $this->settings()->get($key, $default);
    }

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

        $this->settings()->fill($settings)->save();

        return $this;
    }

    public function group(string $group): self
    {
        return new self($this->manager, $group);
    }

    protected function settings(): AbstractSettings
    {
        $settings = $this->manager->resolveSettings($this->group);

        if (blank($settings)) {
            throw new RuntimeException("Settings group [$this->group] not found.");
        }

        return $settings;
    }
}
