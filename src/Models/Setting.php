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
 * @mixin Model
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

    /**
     * Lock setting
     */
    public function lock(): void
    {
        if ($this->locked()) {
            return;
        }

        $this->locked = true;

        $this->save();
    }

    /**
     * Unlock setting
     */
    public function unlock(): void
    {
        if (! $this->locked()) {
            return;
        }

        $this->locked = false;

        $this->save();
    }

    /**
     * Checked if setting is locked
     */
    public function locked(): bool
    {
        return $this->locked;
    }

    /**
     * Scoped by group
     *
     * @param mixed $query Model query
     * @param string $group The group to filter for
     * @return void
     */
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
        if ($setting->locked()) {
            throw new LockedSettingException($setting->name);
        }
    }

    public static function booted(): void
    {
        self::updating(self::verifiedIsLocked(...));
        self::deleting(self::verifiedIsLocked(...));
    }
}
