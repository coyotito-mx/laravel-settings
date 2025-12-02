<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Repositories;

use Coyotito\LaravelSettings\Casters\Contracts\PrepareValue;
use Coyotito\LaravelSettings\Casters\PrepareEloquentValue;
use Coyotito\LaravelSettings\Models\Setting;
use Coyotito\LaravelSettings\AbstractSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Eloquent repository for settings storage.
 *
 * This repository uses Eloquent models to persist settings in a database.
 */
class EloquentRepository extends BaseRepository
{
    /**
     * Construct the Eloquent repository using the given model as the source
     *
     * @param class-string<Setting> $model The Eloquent model class to use for settings storage
     * @param string $group The group name for the settings
     */
    public function __construct(protected string $model, string $group = AbstractSettings::DEFAULT_GROUP)
    {
        parent::__construct($group);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    protected function updateMany(array|Collection $settings): void
    {
        $this->upsertMany($settings);
    }

    /**
     * {@inheritdoc}
     */
    protected function insertMany(array|Collection $settings): void
    {
        $settings = $settings instanceof Collection ? $settings->toArray() : $settings;

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
     * {@inheritdoc}
     */
    protected function upsertMany(array|Collection $settings): void
    {
        $settings = $settings instanceof Collection ? $settings->toArray() : $settings;

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
     * {@inheritdoc}
     */
    protected function deleteMany(array $settings): void
    {
        $this->query()->whereIn('name', $settings)->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function drop(): void
    {
        $this->query()->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function renameGroup(string $newGroup): void
    {
        if ($this->query()->update(['group' => $newGroup])) {
            $this->group = $newGroup;
        }
    }

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
