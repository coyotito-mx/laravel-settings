<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Database\Schema;

use Illuminate\Support\Facades\Facade;

/**
 * Settings Facade
 *
 * @mixin Builder
 *
 * @package Coyotito\LaravelSettings
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'settings.schema';
    }
}
