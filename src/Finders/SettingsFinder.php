<?php

declare(strict_types=1);


namespace Coyotito\LaravelSettings\Finders;

use Illuminate\Filesystem\Filesystem;

class SettingsFinder
{
    public function __construct(Filesystem $files)
    {
        //
    }

    public function discover(string $namespace): ?array
    {
        //
    }
}
