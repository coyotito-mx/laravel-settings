<?php

namespace Coyotito\LaravelSettings\Helpers {

    use Coyotito\LaravelSettings\SettingsService;
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
            realpath(__DIR__ . DIRECTORY_SEPARATOR . '..'),
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
        $namespace = psr4_namespace_normalizer($namespace);
        $namespaces = app('class-loader')->getPrefixesPsr4();

        if ($namespaces[$namespace] ?? null) {
            return $namespaces[$namespace][0];
        }

        $builtNamespace = '';
        $namespaceSegments = Str::of($namespace)->explode('\\')->filter();

        foreach ($namespaceSegments as $segment) {
            $builtNamespace .= "$segment\\";

            if (isset($namespaces[$builtNamespace])) {
                $remainingSegments = Str::of($namespace)->replace($builtNamespace, '')->explode('\\')->filter();

                return join_paths($namespaces[$builtNamespace][0], ...$remainingSegments);
            }
        }

        return null;
    }

    /**
     * Normalize the Namespace
     *
     * This normalization pairs with how Composer register PSR-4 namespaces, with a leading `\` only (escaped, actually);
     */
    function psr4_namespace_normalizer(string $namespace): string
    {
        return (string) Str::of($namespace)->trim('\\')->append('\\');
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
        if (is_array($setting) && !array_is_list($setting)) {
            return $service->set($setting, $default);
        }

        // If $default is not an array, is simple get
        if (!is_array($default)) {
            return $service->get($setting, $default);
        }

        // If $default is an array, treat as group get / set
        $group = $service->group($setting);

        // If $default is a list, treat as group get, otherwise group set
        return array_is_list($default) ? $group->get($default) : $group->set($default);
    }
}
