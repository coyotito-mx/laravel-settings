<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Facades;

use Coyotito\LaravelSettings\SettingsManager as Manager;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin Manager
 */
class SettingsManager extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'settings.manager';
    }
}
