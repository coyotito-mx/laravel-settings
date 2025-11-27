<?php

namespace Coyotito\LaravelSettings\Helpers
{

    use Illuminate\Support\Facades\File;
    use Illuminate\Support\Str;

    use function Illuminate\Filesystem\join_paths;

    /**
     * Get the package path relative to the package root namespace `Coyotito\LaravelSettings`
     *
     * @param string ...$path
     * @return string
     */
    function package_path(string ...$path): string
    {
        $path = array_filter(
            array_map(fn (string $segment) => trim($segment, DIRECTORY_SEPARATOR), $path),
            filled(...)
        );

        return join_paths(
            realpath(__DIR__.DIRECTORY_SEPARATOR.'..'),
            ...$path
        );
    }

    /**
     * Convert a PSR-4 namespace to a file path
     *
     * The namespace must be defined in composer.json `autoload.psr-4` and the path must exist in the base path.
     *
     * @internal
     *
     * @param string $namespace The namespace to resolve to a path
     * @return ?string
     */
    function psr4_namespace_to_path(string $namespace): ?string
    {
        static $composer = File::json(base_path('composer.json'));

        if (! data_has($composer, 'autoload.psr-4')) {
            return null;
        }

        foreach (data_get($composer, 'autoload.psr-4') as $prefix => $path) {
            $path = rtrim($path, '/\\');

            if (str_starts_with($namespace, rtrim($prefix, '\\'))) {
                $remaining = Str::after($namespace, rtrim($prefix, '\\'));
                $segments = $remaining === '' ? [] : array_filter(explode('\\', $remaining), filled(...));

                return join_paths(
                    base_path($path),
                    ...$segments
                );
            }
        }

        return null;
    }
}
