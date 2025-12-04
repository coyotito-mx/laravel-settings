<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Facades;

use Coyotito\LaravelSettings\SettingsManager as Manager;
use Illuminate\Support\Facades\Facade;

/**
 * Settings Manager Facade
 *
 * @mixin Manager
 *
 * @package Coyotito\LaravelSettings
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
