<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Models\Exceptions;

/**
 * Exception to express when a setting in Locked and cannot be updated or deleted
 */
class LockedSettingException extends \Exception
{
    public function __construct(protected(set) string|array $setting)
    {
        $word = 'setting';
        $plural = 'is';

        if (is_array($setting) && count($setting) > 1) {
            $word = 'settings';
            $plural = 'are';
        }

        parent::__construct("The $word provided $plural locked and cannot be modified.");
    }
}
