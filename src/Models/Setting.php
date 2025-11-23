<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Models;

use Coyotito\LaravelSettings\Models\Exceptions\LockedSettingException;
use Illuminate\Database\Eloquent\Model;

/**
 *
 * @method \Illuminate\Database\Eloquent\Builder<self> byGroup(string $group)
 *
 * @property string $name Name of the setting
 * @property string $group Group where the setting is stored
 * @property mixed $payload The value of the setting
 * @property bool $locked if the setting can be updated or deleted
 *
 * @package Coyotito\LaravelSettings
 */
final class Setting extends Model
{
    public $fillable = [
        'name',
        'group',
        'payload',
        'locked',
    ];

    public $casts = [
        'locked' => 'boolean',
        'payload' => 'json',
    ];

    public function locked(): void
    {
        if ($this->locked) {
            return;
        }

        $this->locked = true;

        $this->save();
    }

    public function unlock(): void
    {
        if (! $this->isLocked()) {
            return;
        }

        $this->locked = false;

        $this->save();
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function scopeByGroup($query, string $group): void
    {
        $query->where('group', $group);
    }

    /**
     * Check if the setting is locked and throw an exception if it is.
     *
     * @param  Setting  $setting  The setting to check
     * @throws LockedSettingException if the setting is locked
     */
    public static function verifiedIsLocked(self $setting): void
    {
        if ($setting->isLocked()) {
            throw new LockedSettingException('Setting is locked');
        }
    }

    public static function booted(): void
    {
        self::updating(self::verifiedIsLocked(...));
        self::deleting(self::verifiedIsLocked(...));
    }
}
