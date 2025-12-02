<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Facades;

use Coyotito\LaravelSettings\LaravelSettingsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin LaravelSettingsManager
 */
class LaravelSettings extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'settings.manager';
    }
}
