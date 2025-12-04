<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Console\Commands\Concerns;

use Coyotito\LaravelSettings\AbstractSettings;

use function Coyotito\LaravelSettings\Helpers\package_path;

/**
 * Trait to interact with stubs.
 *
 * @internal
 *
 * @package Coyotito\LaravelSettings
 */
trait InteractsWithStubs
{
    /**
     * Resolve the stub file name based on the type and group.
     *
     * @param string $type The type of the stub (class|migration)
     */
    protected function resolveStub(string $type): string
    {
        $group = AbstractSettings::DEFAULT_GROUP;

        if ($this->getGroup() !== AbstractSettings::DEFAULT_GROUP) {
            $group = 'group';
        }

        return "$type-$group.stub";
    }

    protected function getStubPath(string ...$path): string
    {
        return package_path('stubs', ...$path);
    }
}
