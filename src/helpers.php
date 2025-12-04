<?php

namespace Coyotito\LaravelSettings\Helpers
{

    use Coyotito\LaravelSettings\SettingsService;
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
     * Get / Update settings values
     *
     *
     * if `$setting` is string and `$default` an `array<int, string>`, the `$setting` will now represent
     * the group from where you want to get the settings, but if the `array` is `array<string, mixed>`, this
     * will represent to update the specified settings.
     *
     * <code>
     *
     * // Get the Settings service instance
     * $settings = settings();
     *
     * // Get settings values
     * // $settings->get('setting');
     * $value = settings('setting');
     *
     * // Get setting with default value
     * // $settings->get('setting', 'default value');
     * $value = settings('setting', 'default value');
     *
     * // Get multiple settings in the default group
     * // $settings->get(['setting1', 'setting2', 'setting3']);
     * $values = settings(['setting1', 'setting2', 'setting3']);
     *
     * // Get multiple settings in a specific group
     * // $settings->group('group')->get(['setting1', 'setting2']);
     * $values = settings('group', ['setting1', 'setting2']);
     *
     * // Set a single setting in the default group
     * // $settings->set(['setting' => 'new value']);
     * settings(['setting' => 'new value']);
     *
     * // Set multiple settings in the default group
     * // $settings->set([
     * //     'setting1' => 'value1',
     * //     'setting2' => 'value2'
     * // ]);
     * settings([
     *     'setting1' => 'value1',
     *     'setting2' => 'value2',
     * ]);
     *
     * // Set multiple settings in a specific group
     * // $settings->group('group')->set([
     * //     'setting1' => 'value1',
     * //     'setting2' => 'value2'
     * // ]);
     * settings('group', [
     *     'setting1' => 'value1',
     *     'setting2' => 'value2',
     * ]);
     * </code>
     */
    function settings(null|string|array $setting = null, mixed $default = null)
    {
        /** @var SettingsService $service */
        $service = app()->make('settings.service');

        // If no arguments, return the service instance
        if ($setting === null) {
            return $service;
        }

        // If $setting is an array and not a list, a massive set is intended
        if (is_array($setting) && ! array_is_list($setting)) {
            return $service->set($setting, $default);
        }

        // If $default is not an array, is simple get
        if (! is_array($default)) {
            return $service->get($setting, $default);
        }

        // If $default is an array, treat as group get / set
        $group = $service->group($setting);

        // If $default is a list, treat as group get, otherwise group set
        return array_is_list($default) ? $group->get($default) : $group->set($default);
    }
}
