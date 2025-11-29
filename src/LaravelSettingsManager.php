<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;

use function Illuminate\Filesystem\join_paths;

class LaravelSettingsManager
{
    protected array $settingsFolders = [];

    /**
     * Add a namespace and its corresponding path for Setting classes.
     */
    public function addNamespace(string $namespace, ?string $path = null): void
    {
        $path = $path ?? psr4_namespace_to_path($namespace);

        if (blank($path)) {
            throw new \InvalidArgumentException("Could not resolve path for namespace: $namespace");
        }

        $this->settingsFolders[trim($namespace, '\\')] = $path;
    }

    public function getClasses(): array
    {
        $classes = [];

        foreach (array_keys($this->settingsFolders) as $namespace) {
            $resolvedClasses = $this->resolveNamespaceClasses($namespace);

            if (is_null($resolvedClasses)) {
                continue;
            }

            $classes = [...$resolvedClasses, ...$classes];
        }

        return $classes;
    }

    protected function resolveNamespaceClasses(string $namespace): ?array
    {
        $directory = $this->settingsFolders[$namespace];

        $files = File::glob(
            join_paths($directory, '*.php')
        );

        if (empty($files)) {
            return null;
        }

        $classes = Arr::map($files, function (string $file) use ($namespace): string {
            $className = pathinfo($file, PATHINFO_FILENAME);

            return "$namespace\\$className";
        });

        return Arr::reject($classes, fn (string $class): bool => ! is_subclass_of($class, Setting::class));
    }
}
