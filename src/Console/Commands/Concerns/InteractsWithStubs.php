<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Console\Commands\Concerns;

use function Coyotito\LaravelSettings\Helpers\package_path;

trait InteractsWithStubs
{
    /**
     *
     * @param string $type The type of the stub (class|migration)
     * @return string
     */
    protected function resolveStub(string $type): string
    {
        $group = 'default';

        if ($this->getGroup('group') !== 'default') {
            $group = 'group';
        }

        return "$type-$group.stub";
    }

    protected function getStubPath(string ...$path): string
    {
        return package_path('stubs', ...$path);
    }
}
