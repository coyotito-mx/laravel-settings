<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Finders;

use Illuminate\Filesystem\Filesystem;

use function Coyotito\LaravelSettings\Helpers\psr4_namespace_to_path;

class SettingsFinder
{
    public function __construct(protected Filesystem $files)
    {
        //
    }

    public function discover(string $namespace): ?array
    {
        $path = $this->resolveNamespacePath($namespace);

        if (blank($path)) {
            return null;
        }

        return $this->files->glob($path . '/*.php') ?: null;
    }

    protected function resolveNamespacePath(string $namespace): ?string
    {
        return psr4_namespace_to_path($namespace);
    }
}
