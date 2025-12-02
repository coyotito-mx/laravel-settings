<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Console\Commands\Concerns;

use function Coyotito\LaravelSettings\Helpers\package_path;

trait InteractsWithStubs
{
    /**
     *
     * @param string $type The type of the stub (class|migration)
     */
    protected function resolveStub(string $type): string
    {
        $group = \Coyotito\LaravelSettings\AbstractSettings::DEFAULT_GROUP;

        if ($this->getGroup() !== \Coyotito\LaravelSettings\AbstractSettings::DEFAULT_GROUP) {
            $group = 'group';
        }

        return "$type-$group.stub";
    }

    protected function getStubPath(string ...$path): string
    {
        return package_path('stubs', ...$path);
    }
}
