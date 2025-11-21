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
     * Add setting in the given group
     *
     * @param string $group
     * @param Closure(group: Blueprint)
     * @return void
     */
    public function in(string $group, \Closure $callback): void
    {
        $repo = tap($this->repo, fn ($repo) => $repo->setGroup($group));
        $blueprint = new Blueprint($repo);

        $callback(group: $blueprint);
    }

    /**
     * Add settings to the default group
     *
     * @param Closure(group: Blueprint) $callback
     * @return void
     */
    public function default(\Closure $callback): void
    {
        $repo = tap($this->repo, fn ($repo) => $repo->setGroup(static::DEFAULT_GROUP));
        $blueprint = new Blueprint($repo);

        $callback( $blueprint);
    }

    /**
     * Remove all the setting from the given group
     */
    public function drop(string $group): void
    {
        $repo = tap($this->repo, fn ($repo) => $repo->setGroup($group));

        $repo->deleteAll();
    }

    /**
     * Rename the a group
     */
    public function rename(string $oldGroup, string $newGroup): void
    {
        $repo = tap($this->repo, fn ($repo) => $repo->setGroup($oldGroup));

        $repo->renameGroup($newGroup);
    }
}
