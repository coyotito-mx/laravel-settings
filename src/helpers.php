<?php

namespace Coyotito\LaravelSettings\Helpers
{

    use Coyotito\LaravelSettings\Settings;
    use Illuminate\Support\Collection;
    use Illuminate\Support\Facades\File;
    use Illuminate\Support\Str;

    use function Illuminate\Filesystem\join_paths;

    /**
     * Get the package path relative to the package root namespace `Coyotito\SettingsManager`
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
     * @param string $namespace The namespace to resolve to a path
     *
     * @internal
     *
     */
    function psr4_namespace_to_path(string $namespace): ?string
    {
        $namespaces = (function (): Collection {
            $autoloadFile = 'vendor/composer/autoload_psr4.php';

            $composerPath = app()->runningUnitTests()
                ? package_path($autoloadFile)
                : base_path($autoloadFile);

            return collect(File::getRequire($composerPath))->map(fn (array $path): string => $path[0]);
        })();

        foreach ($namespaces as $prefix => $path) {
            $path = rtrim($path, '/\\');

            if (str_starts_with($namespace, rtrim($prefix, '\\'))) {
                $remaining = Str::after($namespace, rtrim($prefix, '\\'));
                $segments = $remaining === '' ? [] : array_filter(explode('\\', $remaining), filled(...));

                return join_paths($path, ...$segments);
            }
        }

        return null;
    }

    /**
     * Get / set settings values
     */
    function settings(null|string|array $setting = null, mixed $default = null)
    {
        $manager = new Settings();

        // If no arguments, return the manager instance
        if ($setting === null) {
            return $manager;
        }

        // If $setting is an array and not a list, a massive set is intended
        if (is_array($setting) && ! array_is_list($setting)) {
            return $manager->set($setting, $default);
        }

        // If $default is not an array, is simple get
        if (! is_array($default)) {
            return $manager->get($setting, $default);
        }

        // If $default is an array, treat as group get / set
        $group = $manager->group($setting);

        // If $default is a list, treat as group get, otherwise group set
        return array_is_list($default) ? $group->get($default) : $group->set($default);
    }
}
