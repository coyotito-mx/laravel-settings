<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Models\Exceptions;

class LockedSettingException extends \Exception
{
    public function __construct(string $settingName)
    {
        parent::__construct("Setting `$settingName` is locked and cannot be updated.");
    }
}
