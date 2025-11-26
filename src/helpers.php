<?php

namespace Coyotito\LaravelSettings\Helpers
{

    use function Orchestra\Sidekick\join_paths;

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
}
