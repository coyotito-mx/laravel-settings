<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Finders;

use Coyotito\LaravelSettings\Settings;
use Illuminate\Filesystem\Filesystem;

use Illuminate\Support\Str;
use function Coyotito\LaravelSettings\Helpers\psr4_namespace_normalizer;
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

        $files = $this->files->glob($path . '/*.php') ?: null;

        $settings = collect($files)
            ->map(function (string $file) use ($namespace) {
                $className = pathinfo($file, PATHINFO_FILENAME);

                return psr4_namespace_normalizer($namespace) . $className;
            })
            ->filter(fn (string $className) => is_subclass_of($className, Settings::class))
            ->toArray();

        return $settings ?: null;
    }

    protected function resolveNamespacePath(string $namespace): ?string
    {
        return psr4_namespace_to_path($namespace);
    }
}
