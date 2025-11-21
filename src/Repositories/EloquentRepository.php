<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Repositories;

use Arr;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class EloquentRepository implements Contracts\Repository
{
    protected ?string $group = null;

    /**
     * Construct the Eloquent repository using the given model as the source
     *
     * @param class-string<\Coyotito\LaravelSettings\Models\Setting> $model
     * @param null|string $group Name of the group
     */
    public function __construct(protected string $model)
    {
        //
    }

    public function get(string|array $setting, mixed $default = null): mixed
    {
        if (func_num_args() === 2 || is_string($setting)) {
            $setting = [
                $setting => $default
            ];
        }

        $settings = $this->withGroup()->whereIn('name', array_is_list($setting) ? $setting : array_keys($setting))->get();

        $collection = collect($setting)
            ->map(function (mixed $value, string $name) use ($settings) {
                return $settings->where('name', $name)->first()?->payload ?? $value;
            });

        if ($collection->count() === 1) {
            return $collection->first();
        }

        return $collection->toArray();
    }

    public function getAll(): array
    {
        return $this->withGroup()->get(['name', 'payload'])->pluck('payload', 'name')->toArray();
    }

    public function update(string|array $setting, mixed $value = null): void
    {
        if (func_num_args() === 2 || is_string($setting)) {
            $setting = [
                $setting => $value
            ];
        }

        if (empty($setting)) {
            return;
        }

        $settingNames = array_keys($setting);

        $presentSettings = $this->withGroup()->whereIn('name', $settingNames)->pluck('name');

        if ($presentSettings->isEmpty()) {
            return;
        }

        $now = now();

        $data = $presentSettings->map(function (string $name) use ($setting, $now): array {
            return [
                'name' => $name,
                'group' => $this->group(),
                'payload' => $this->castValue($setting[$name] ?? null),
                'updated_at' => $now,
            ];
        })->toArray();

        $this->query()->upsert($data, ['group', 'name'], ['payload', 'updated_at']);
    }

    public function insert(string|array $setting, mixed $value = null): void
    {
        if (func_num_args() === 2 || is_string($setting)) {
            $setting = [
                $setting => $value
            ];
        }

        if (empty($setting)) {
            return;
        }

        $settingNames = array_keys($setting);

        $presentSettings = $this->withGroup()->whereIn('name', $settingNames)->pluck('name');

        $now = now();

        $data = collect($setting)
            ->diffKeys($presentSettings)
            ->map(function (mixed $value, string $name) use ($now): array {
                return [
                    'name' => $name,
                    'group' => $this->group(),
                    'payload' => $this->castValue($value ?? null),
                    'updated_at' => $now,
                    'created_at' => $now,
                ];
            })->toArray();


        $this->query()->insert($data);
    }

    public function delete(string|array $setting): int
    {
        $setting = Arr::wrap($setting);

        if (empty($setting)) {
            return 0;
        }

        return (int) $this->withGroup()->whereIn('name', $setting)->delete();
    }

    public function group(): string
    {
        if (! filled($this->group)) {
            throw new RuntimeException('The group must not be empty');
        }

        return $this->group;
    }

    public function setGroup(string $group): void
    {
        $this->group = $group;
    }

    public function drop(): void
    {
        $this->withGroup()->delete();
    }

    public function renameGroup(string $newGroup): void
    {
        if ($this->withGroup()->update(['group' => $newGroup])) {
            $this->setGroup($newGroup);
        }
    }

    /**
     * @return Builder<\Coyotito\LaravelSettings\Models\Setting>
     */
    protected function query(): Builder
    {
        return $this->model::query();
    }

    protected function withGroup(): Builder
    {
        return $this->query()->byGroup($this->group());
    }

    protected function castValue(mixed $value): ?string
    {
        return ! is_null($value) ? json_encode($value) : null;
    }
}
