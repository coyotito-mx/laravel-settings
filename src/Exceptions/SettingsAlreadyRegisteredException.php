<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Exceptions;

use Exception;

class SettingsAlreadyRegisteredException extends Exception
{
    public function __construct(protected(set) string $settings)
    {
        $settings = class_basename($settings);

        parent::__construct("The [$settings] is already registered with the group");
    }
}
