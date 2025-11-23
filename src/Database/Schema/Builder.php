<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Database\Schema;

use Closure;
use Coyotito\LaravelSettings\Repositories\Contracts\Repository;

final class Builder
{
    public const string DEFAULT_GROUP = 'default';

    public function __construct(protected Repository $repo)
    {
        //
    }

    /**
     * Add settings to the given group
     *
     * @param string $group
     * @param \Closure(Blueprint): void $callback
     * @return void
     */
    public function in(string $group, \Closure $callback): void
    {
        $repo = tap($this->repo, fn ($repo) => $repo->setGroup($group));
        $blueprint = new Blueprint($repo);

        $callback($blueprint);
    }

    /**
     * Add settings to the default group
     *
     * @param Closure(Blueprint): void $callback
     * @return void
     */
    public function default(\Closure $callback): void
    {
        $repo = tap($this->repo, fn ($repo) => $repo->setGroup(static::DEFAULT_GROUP));
        $blueprint = new Blueprint($repo);

        $callback($blueprint);
    }

    /**
     * Drop all the settings from the given group
     */
    public function drop(string $group): void
    {
        tap($this->repo, fn ($repo) => $repo->setGroup($group))->drop();
    }

    /**
     * Rename the given group
     */
    public function rename(string $oldGroup, string $newGroup): void
    {
        $repo = tap($this->repo, fn ($repo) => $repo->setGroup($oldGroup));

        $repo->renameGroup($newGroup);
    }
}
