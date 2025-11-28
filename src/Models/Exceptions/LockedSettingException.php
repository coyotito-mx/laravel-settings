<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Models\Exceptions;

use Illuminate\Support\Pluralizer;

/**
 * Exception to express when a setting in Locked and cannot be updated or deleted
 */
class LockedSettingException extends \Exception
{
    public function __construct(protected(set) string|array $setting)
    {
        $settings = is_array($setting) ? $setting : [$setting];
        $settingsCount = count($settings);

        $settingWord = Pluralizer::plural('setting', $settingsCount);
        $toBe = $settingsCount > 1 ? 'are' : 'is';

        parent::__construct(
            sprintf(
                'The %s provided %s locked and cannot be modified.',
                $settingWord,
                $toBe
            )
        );
    }
}
