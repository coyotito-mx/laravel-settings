<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use Coyotito\LaravelSettings\Exceptions\SettingsManifestCannotLoadException;
use Exception;
use Illuminate\Filesystem\Filesystem;

class SettingsManifest
{
    /**
     * Constructor.
     *
     * @param Filesystem $files The filesystem instance.
     * @param string $manifestPath The path to the manifest file.
     */
    public function __construct(protected Filesystem $files, protected string $manifestPath)
    {
        //
    }

    public function present(): bool
    {
        return $this->files->exists($this->getManifestPath());
    }

    /**
     * Load the settings manifest.
     *
     * @throws Exception if the manifest file is not found
     */
    public function load(): array
    {
        if (! $this->present()) {
            throw new SettingsManifestCannotLoadException("Settings manifest not found at [{$this->getManifestPath()}].");
        }

        return $this->files->getRequire($this->manifestPath);
    }

    /**
     * Generate the settings manifest.
     *
     * @template Settings of class-string<Settings>
     *
     * @param array<string, Settings> $settings
     */
    public function generate(array $settings): void
    {
        $content = '<?php return ' . var_export($settings, true) . ';' . PHP_EOL;

        $this->files->put($this->getManifestPath(), $content);
    }

    /**
     * Get the manifest path.
     */
    public function getManifestPath(): string
    {
        return $this->manifestPath;
    }
}
