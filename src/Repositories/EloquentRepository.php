<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Repositories;

use Coyotito\LaravelSettings\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;

class EloquentRepository implements Contracts\Repository
{
    protected ?string $group = null;

    /**
     * @param class-string<\Coyotito\LaravelSettings\Models\Setting> $model
     * @param null|string $group Name of the group
     */
    public function __construct(protected string $model)
    {
        //
    }

    public function get(string $name, mixed $default = null): mixed
    {
        try {
            return $this->withGroup()->where('name', $name)->firstOrFail()->payload;
        } catch (ModelNotFoundException $e) {
            return $default;
        }
    }

    public function getAll(): array
    {
        return $this->withGroup()->get()->mapWithKeys(function (Setting $item) {
            return [$item->name => $item->payload];
        })->all();
    }

    public function update(string $name, mixed $value): void
    {
        $this->withGroup()->where('name', $name)->update(['payload' => $this->castValue($value)]);
    }

    public function updateMany(array $settings): void
    {
        if (empty($settings)) {
            return;
        }

        $settingNames = array_keys($settings);

        $presentSettings = $this->withGroup()->whereIn('name', $settingNames)->pluck('name');

        if ($presentSettings->isEmpty()) {
            return;
        }

        $now = now();

        $data = $presentSettings->map(function (string $name) use ($settings, $now): array {
            return [
                'name' => $name,
                'group' => $this->getGroup(),
                'payload' => $this->castValue($settings[$name] ?? null),
                'updated_at' => $now,
            ];
        })->toArray();

        $this->query()->upsert($data, ['group', 'name'], ['payload', 'updated_at']);
    }

    public function insertMany(array $settings): void
    {
        if (empty($settings)) {
            return;
        }

        $settingNames = array_keys($settings);

        $presentSettings = $this->withGroup()->whereIn('name', $settingNames)->pluck('name');

        $now = now();

        $data = collect($settings)
            ->diffKeys($presentSettings)
            ->map(function (mixed $value, string $name) use ($now): array {
                return [
                    'name' => $name,
                    'group' => $this->getGroup(),
                    'payload' => $this->castValue($value ?? null),
                    'updated_at' => $now,
                    'created_at' => $now,
                ];
            })->toArray();


        $this->query()->insert($data);
    }

    public function delete(string $name): void
    {
        $this->withGroup()->where('name', $name)->delete();
    }

    public function deleteMany(array $names): int
    {
        if (empty($names)) {
            return 0;
        }

        return (int) $this->withGroup()->whereIn('name', $names)->delete();
    }

    public function getGroup(): string
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

    public function deleteAll(): void
    {
        $this->withGroup()->delete();
    }

    public function renameGroup($newGroup): void
    {
        $this->withGroup()->update(['group' => $newGroup]);
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
        return $this->query()->byGroup($this->getGroup());
    }

    protected function castValue(mixed $value): ?string
    {
        return ! is_null($value) ? json_encode($value) : null;
    }
}
