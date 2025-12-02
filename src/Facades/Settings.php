<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Coyotito\LaravelSettings\Settings
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'settings.service';
    }
}
