<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Facades;

use Coyotito\LaravelSettings\Database\Schema\Builder;
use Illuminate\Support\Facades\Facade;

/**
 * Schema class to define settings
 *
 * @mixin Builder
 *
 * @package Coyotito\LaravelSettings
 */
class Schema extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'settings.schema';
    }
}
