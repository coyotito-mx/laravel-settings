<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Example Facade
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
