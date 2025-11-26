<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Models\Exceptions;

/**
 * Exception to express when a setting in Locked and cannot be updated or deleted
 */
class LockedSettingException extends \Exception
{
    public function __construct(string $settingName)
    {
        parent::__construct("Setting `$settingName` is locked and cannot be updated/deleted.");
    }
}
