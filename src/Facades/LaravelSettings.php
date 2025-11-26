<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Coyotito\LaravelSettings\LaravelSettingsManager
 */
class LaravelSettings extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor()
    {
        return 'settings.manager';
    }
}
