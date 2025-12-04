<?php

namespace Coyotito\LaravelSettings\Repositories;

use Coyotito\LaravelSettings\Settings;
use Illuminate\Support\Collection;

/**
 * In-memory repository for settings storage.
 *
 * This repository stores settings in memory and does not persist them.
 * It is useful for testing or temporary settings storage.
 *
 * @internal
 *
 * @package Coyotito\LaravelSettings
 */
final class InMemoryRepository extends BaseRepository
{
    protected Collection $storage;

    public function __construct(string $group = Settings::DEFAULT_GROUP)
    {
        parent::__construct($group);

        $this->storage = collect([$group => collect()]);
    }

    /**
     * {@inheritDoc}
     */
    protected function getSettings(?array $settings = null): Collection
    {
        $storage = $this->storage();

        if (filled($settings)) {
            $storage = $storage->only($settings);
        }

        return $this->normalizeSettings(
            $storage->pluck('payload', 'name')->all(),
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function updateMany(array $settings): void
    {
        $this->upsertMany($settings);
    }

    /**
     * {@inheritDoc}
     */
    protected function insertMany(array $settings): void
    {
        $this->upsertMany($settings);
    }

    /**
     * {@inheritDoc}
     */
    protected function upsertMany(array $settings): void
    {
        $now = now();

        foreach ($settings as $name => $data) {
            /** @var ?Collection<string, mixed> $setting */
            $setting = $this->storage()->get($name);


            if (filled($setting)) { // Update setting
                $setting = $setting->merge([
                    'payload' => $data['payload']->transform(),
                    'updated_at' => $now,
                ]);
            } else { // Insert setting
                $setting = collect([
                    'name' => $name,
                    'payload' => $data['payload']->transform(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->storage()->put($name, $setting);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function deleteMany(array $settings): void
    {
        $this->storage()->forget($settings);
    }

    /**
     * {@inheritDoc}
     */
    public function drop(): void
    {
        tap(
            $this->storage,
            fn (Collection $storage) => $storage->forget([$this->group])
        )->put($this->group, collect());
    }

    /**
     * {@inheritDoc}
     */
    public function renameGroup(string $newGroup): void
    {
        $settings = $this->storage();

        $this->storage
            ->forget([$this->group])
            ->put($this->group = $newGroup, $settings);
    }

    /**
     * Access the internal storage
     */
    protected function storage(): Collection
    {
        /** @var ?Collection $group */
        $group = $this->storage->get($this->group);

        if (blank($group)) {
            $group = collect();

            $this->storage->put($this->group, $group);
        }

        return $group;
    }
}
