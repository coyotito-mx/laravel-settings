<?php

use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use Coyotito\LaravelSettings\Settings;

beforeEach(function () {
    /** @var Repository $repo */
    $repo = app()->make('repository.in-memory');
    $repo->group = Settings::DEFAULT_GROUP;

    $this->repo = $repo;
});

it('can fill properties', function () {
    $this->repo->insert([
        'name' => 'Coyotito',
        'description' => 'Lorem ipsum dolor it',
    ]);

    expect($this->repo->get(['name', 'description']))
        ->name->toBe('Coyotito')
        ->description->toBe('Lorem ipsum dolor it');
});

it('update settings', function () {
    $this->repo->insert([
        'debug' => true,
        'config' => ['name' => 'Coyotito', 'description' => 'Lorem ipsum'],
    ]);

    $this->repo->update([
        'debug' => false,
        'config' => [
            'name' => 'Coyotito Rocks!',
            'description' => 'Lorem ipsum dolor it',
        ],
    ]);

    expect($this->repo->getAll())
        ->debug->toBeFalse()
        ->config->toBe([
            'name' => 'Coyotito Rocks!',
            'description' => 'Lorem ipsum dolor it',
        ]);
});

test('dynamic properties are not persisted', function () {
    $this->repo->insert(['name' => 'Coyotito']);

    expect($this->repo->get(['name', 'description']))
        ->dynamic->not->toBe('Hello, world!')
        ->name->toBe('Coyotito');

    $this->repo->update([
        'dynamic' => 'Coyotito',
        'name' => 'Hello, World!',
    ]);

    expect($this->repo->getAll())
        ->dynamic->toBeNull()
        ->name->toBe('Hello, World!');
});

it('gets a single setting with default when not found', function () {
    expect($this->repo)
        ->get('non_existing_setting', 'fallback')
        ->toBe('fallback')
        ->getAll()->toHaveCount(0);
});

it('gets multiple settings as associative array', function () {
    $this->repo->insert([
        'name' => 'Coyotito',
        'debug' => true,
    ]);

    $settings = $this->repo->get(['name', 'debug']);

    expect($settings)
        ->toBeArray()
        ->toHaveKeys(['name', 'debug'])
        ->name->toBe('Coyotito')
        ->debug->toBeTrue();
});

it('inserts single and multiple settings', function () {
    $this->repo->insert('name', 'Coyotito');

    $this->repo->insert([
        'debug' => true,
        'env' => 'local',
    ]);

    expect($this->repo->getAll())
        ->name->toBe('Coyotito')
        ->debug->toBeTrue()
        ->env->toBe('local');
});

it('updates existing settings', function () {
    $this->repo->insert([
        'name' => 'Coyotito',
        'debug' => true,
    ]);

    $this->repo->update('name', 'Coyotito Rocks!');
    $this->repo->update(['debug' => false]);

    expect($this->repo->getAll())
        ->name->toBe('Coyotito Rocks!')
        ->debug->toBeFalse();
});

it('upserts single and multiple settings', function () {
    tap($this->repo)
        ->upsert('name', 'Coyotito')
        ->upsert([
            'debug' => true,
            'env' => 'local',
        ]);

    expect($this->repo->getAll())
        ->name->toBe('Coyotito')
        ->debug->toBeTrue()
        ->env->toBe('local')
        ->version->toBeNull();

    tap($this->repo)
        ->upsert('name', 'Coyotito Rocks!')
        ->upsert([
            'debug' => false,
            'version' => '1.0.0',
        ]);

    expect((object) $this->repo->getAll())
        ->name->toBe('Coyotito Rocks!')
        ->debug->toBeFalse()
        ->env->toBe('local')
        ->version->toBe('1.0.0');
});

it('deletes single and multiple settings', function () {
    $this->repo->insert([
        'first' => 1,
        'second' => 2,
        'third' => 3,
    ]);

    $this->repo->delete('first');

    expect($this->repo->getAll())->toHaveCount(2);

    $this->repo->delete(['second', 'missing']);

    expect($this->repo->getAll())->toHaveCount(1);
});

it('drops all settings for current group only', function () {
    $other = app('repository.in-memory');
    $default = app('repository.in-memory');

    $default->insert([
        'name' => 'Coyotito',
        'debug' => true,
    ]);

    $other->insert('name', 'Coyotito');

    $default->drop();

    expect($this->repo->getAll())->toHaveCount(0)
        ->and($other->getAll())->toHaveCount(1);
});

it('renames group and moves all settings to the new group', function () {
    $this->repo->insert([
        'name' => 'Coyotito',
        'debug' => true,
    ]);

    expect($this->repo)
        ->group->toBe(Settings::DEFAULT_GROUP)
        ->getAll()->toHaveCount(2);

    $this->repo->renameGroup('other');

    expect($this->repo)
        ->group->toBe('other')
        ->getAll()->toHaveCount(2);
});
