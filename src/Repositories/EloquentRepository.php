<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Repositories;

use Coyotito\LaravelSettings\Casters\Contracts\PrepareValue;
use Coyotito\LaravelSettings\Casters\PrepareEloquentValue;
use Coyotito\LaravelSettings\Models\Setting;
use Coyotito\LaravelSettings\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Eloquent repository for settings storage.
 *
 * This repository uses Eloquent models to persist settings in a database.
 *
 * @package Coyotito\LaravelSettings
 */
class EloquentRepository extends BaseRepository
{
    /**
     * Construct the Eloquent repository using the given model as the source
     *
     * @param class-string<Setting> $model The Eloquent model class to use for settings storage
     * @param string $group The group name for the settings
     */
    public function __construct(protected string $model, string $group = Settings::DEFAULT_GROUP)
    {
        parent::__construct($group);
    }

    /**
     * {@inheritDoc}
     */
    protected function getSettings(?array $settings = null): Collection
    {
        $query = $this->query();

        if (is_array($settings)) {
            $query->whereIn('name', $settings);
        }

        return $this->normalizeSettings(
            $query->pluck('payload', 'name')->all()
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function updateMany(array|Collection $settings): void
    {
        $this->upsertMany($settings);
    }

    /**
     * {@inheritDoc}
     */
    protected function insertMany(array|Collection $settings): void
    {
        $now = now();
        $records = [];

        foreach ($settings as $name => $setting) {
            $records[] = [
                'group' => $this->group,
                'name' => $name,
                'payload' => $setting['payload']->transform(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ($this->model)::insert($records);
    }

    /**
     * {@inheritDoc}
     */
    protected function upsertMany(array $settings): void
    {
        $now = now();
        $records = [];

        foreach ($settings as $name => $setting) {
            $records[] = [
                'group' => $this->group,
                'name' => $name,
                'payload' => $setting['payload']->transform(),
                'updated_at' => $now,
            ];
        }

        ($this->model)::upsert($records, ['group', 'name'], ['payload', 'updated_at']);
    }

    /**
     * {@inheritDoc}
     */
    protected function deleteMany(array $settings): void
    {
        $this->query()->whereIn('name', $settings)->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function drop(): void
    {
        $this->query()->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function renameGroup(string $newGroup): void
    {
        if ($this->query()->update(['group' => $newGroup])) {
            $this->group = $newGroup;
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function castValue(mixed $value): PrepareValue
    {
        return new PrepareEloquentValue($value);
    }

    /**
     * Get the base query builder for the current group.
     */
    private function query(): Builder
    {
        return ($this->model)::byGroup($this->group);
    }
}
