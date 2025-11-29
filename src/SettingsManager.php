<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use Coyotito\LaravelSettings\Repositories\Contracts\Repository;

final class SettingsManager
{
    public function __construct(protected ?Repository $repository = null)
    {
        if (is_null($this->repository)) {
            $this->repository = app(Repository::class);
        }

        $this->setGroup('default');
    }

    public function get(string|array $key, mixed $default = null): mixed
    {
        if (is_null($default)) {
            return $this->repository->get($key);
        }

        return $this->repository->get($key, $default);
    }

    public function set(string|array $values, mixed $default = null): self
    {
        if (is_null($default)) {
            $this->repository->upsert($values);
        } else {
            $this->repository->upsert($values, $default);
        }

        return $this;
    }

    public function group(string $group): self
    {
        return tap(new self($this->repository), fn (self $manager) => $manager->setGroup($group));
    }

    protected function setGroup(string $group): void
    {
        $this->repository->setGroup($group);
    }
}
