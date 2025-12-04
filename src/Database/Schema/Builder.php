<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Database\Schema;

use Closure;
use Coyotito\LaravelSettings\Settings;
use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use Illuminate\Support\Arr;

/**
 * Builder class
 *
 * Used to build settings schema operations
 *
 * @package Coyotito\LaravelSettings
 */
final class Builder
{
    public const string DEFAULT_GROUP = Settings::DEFAULT_GROUP;

    public function __construct(protected Repository $repo)
    {
        //
    }

    /**
     * Add settings to the given group
     *
     * @param string $group Name of the group
     * @param Closure(Blueprint $group): void $callback
     */
    public function in(string $group, Closure $callback): void
    {
        $repo = tap($this->repo, fn ($repo) => $repo->group = $group);
        $blueprint = new Blueprint($repo);

        $callback($blueprint);
    }

    /**
     * Add settings to the default group
     *
     * @param Closure(Blueprint $group): void $callback
     */
    public function default(Closure $callback): void
    {
        $repo = tap($this->repo, fn ($repo) => $repo->group = self::DEFAULT_GROUP);
        $blueprint = new Blueprint($repo);

        $callback($blueprint);
    }

    /**
     * Delete setting(s) from the given group
     */
    public function delete(string|array $settings): void
    {
        $repo = tap($this->repo, fn ($repo) => $repo->group = self::DEFAULT_GROUP);
        $blueprint = new Blueprint($repo);

        if (is_string($settings)) {
            $settings = Arr::wrap($settings);
        }

        foreach ($settings as $setting) {
            $blueprint->remove($setting);
        }
    }

    /**
     * Drop all the settings from the given group
     */
    public function drop(string $group): void
    {
        tap($this->repo, fn ($repo) => $repo->group = $group)->drop();
    }

    /**
     * Rename the given group
     */
    public function rename(string $oldGroup, string $newGroup): void
    {
        $repo = tap($this->repo, fn ($repo) => $repo->group = $oldGroup);

        $repo->renameGroup($newGroup);
    }
}
