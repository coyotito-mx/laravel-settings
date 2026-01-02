<?php

declare(strict_types=1);


namespace Coyotito\LaravelSettings\Exceptions;

use Exception;

class GroupAlreadyRegisteredException extends Exception
{
    public function __construct(protected(set) string $settings, protected(set) string $registered, protected(set) string $group)
    {
        $settings = class_basename($this->settings);

        parent::__construct("The settings [$settings] cannot be registered at group [$group]");
    }
}
