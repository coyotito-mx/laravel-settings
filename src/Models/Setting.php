<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 *
 * @method Builder<self> byGroup(string $group)
 *
 * @property string $name Name of the setting
 * @property string $group Group where the setting is stored
 * @property mixed $payload The value of the setting
 * @property bool $locked if the setting can be updated or deleted
 *
 * @mixin Model
 *
 * @internal
 *
 * @package Coyotito\SettingsManager
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
    ];

    /**
     * Scoped by group
     *
     * @param mixed $query Model query
     * @param string $group The group to filter for
     */
    public function scopeByGroup($query, string $group): void
    {
        $query->where('group', $group);
    }
}
