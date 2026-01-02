<?php

declare(strict_types=1);


namespace Coyotito\LaravelSettings;

use League\Flysystem\Filesystem;

class SettingsManifest
{
    public function __construct(protected Filesystem $files)
    {
        //
    }

    public function present(): bool
    {
        //
    }

    public function load(): array
    {

    }
}
