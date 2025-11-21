<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Schema class to define settings
 *
 * @mixin \Coyotito\LaravelSettings\Database\Schema\Builder
 */
class Schema extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'settings.schema';
    }
}
