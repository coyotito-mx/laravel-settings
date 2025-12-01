<?php

use Coyotito\LaravelSettings\Models\Setting;
use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use Coyotito\LaravelSettings\Settings;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    rmdir_recursive(database_path('migrations'), delete_root: false);

    /** @var Repository $repo */
    $repo = app()->make('repository.eloquent');
    $repo->group = Settings::DEFAULT_GROUP;

    artisan('vendor:publish --tag=laravel-settings-migrations');
    artisan('migrate');

    $this->repo = $repo;
});

afterEach(function () {
    rmdir_recursive(database_path('migrations'), delete_root: false);
});

it('has settings table', function () {
    $table = new Setting()->getTable();

    expect(Schema::hasTable($table))->toBeTrue();
});

it('has seeded settings', function () {
    $table = new Setting()->getTable();

    Setting::insert([
        ['group' => Settings::DEFAULT_GROUP, 'name' => 'name', 'payload' => json_encode('Coyotito')],
        ['group' => Settings::DEFAULT_GROUP, 'name' => 'debug', 'payload' => json_encode(true)],
    ]);

    assertDatabaseCount($table, 2);

    $settings = (object) $this->repo->getAll();

    expect($settings)
        ->name->toBe('Coyotito')
        ->debug->toBeTrue();
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
    Setting::insert([
        ['group' => Settings::DEFAULT_GROUP, 'name' => 'name', 'payload' => json_encode('Coyotito')],
    ]);

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
    $value = $this->repo->get('non_existing', 'fallback');

    expect($value)->toBe('fallback')
        ->and(Setting::byGroup(Settings::DEFAULT_GROUP)->count())->toBe(0);
});

it('gets multiple settings as associative array', function () {
    Setting::insert([
        ['group' => Settings::DEFAULT_GROUP, 'name' => 'name', 'payload' => json_encode('Coyotito')],
        ['group' => Settings::DEFAULT_GROUP, 'name' => 'debug', 'payload' => json_encode(true)],
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

    $table = (new Setting())->getTable();

    assertDatabaseCount($table, 3);

    assertDatabaseHas($table, [
        'group' => Settings::DEFAULT_GROUP,
        'name' => 'name',
        'payload' => json_encode('Coyotito'),
    ]);

    assertDatabaseHas($table, [
        'group' => Settings::DEFAULT_GROUP,
        'name' => 'debug',
        'payload' => json_encode(true),
    ]);

    assertDatabaseHas($table, [
        'group' => Settings::DEFAULT_GROUP,
        'name' => 'env',
        'payload' => json_encode('local'),
    ]);
});

it('updates existing settings', function () {
    Setting::insert([
        ['group' => Settings::DEFAULT_GROUP, 'name' => 'name', 'payload' => json_encode('Coyotito')],
        ['group' => Settings::DEFAULT_GROUP, 'name' => 'debug', 'payload' => json_encode(true)],
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

    expect((object) $this->repo->getAll())
        ->name->toBe('Coyotito')
        ->debug->toBeTrue()
        ->env->toBe('local');

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
    Setting::insert([
        ['group' => Settings::DEFAULT_GROUP, 'name' => 'first', 'payload' => json_encode(1)],
        ['group' => Settings::DEFAULT_GROUP, 'name' => 'second', 'payload' => json_encode(2)],
        ['group' => Settings::DEFAULT_GROUP, 'name' => 'third', 'payload' => json_encode(3)],
    ]);

    $this->repo->delete('first');

    expect($this->repo->getAll())->toHaveCount(2);

    $this->repo->delete(['second', 'missing']);

    expect(Setting::byGroup(Settings::DEFAULT_GROUP)->count())->toBe(1);
});

it('drops all settings for current group only', function () {
    Setting::insert([
        ['group' => Settings::DEFAULT_GROUP, 'name' => 'name', 'payload' => json_encode('Coyotito')],
        ['group' => Settings::DEFAULT_GROUP, 'name' => 'debug', 'payload' => json_encode(true)],
        ['group' => 'other', 'name' => 'name', 'payload' => json_encode('Other')],
    ]);

    $this->repo->drop();

    expect(Setting::byGroup(Settings::DEFAULT_GROUP)->count())->toBe(0)
        ->and(Setting::byGroup('other')->count())->toBe(1);
});

it('renames group and moves all settings to the new group', function () {
    Setting::insert([
        ['group' => Settings::DEFAULT_GROUP, 'name' => 'name', 'payload' => json_encode('Coyotito')],
        ['group' => Settings::DEFAULT_GROUP, 'name' => 'debug', 'payload' => json_encode(true)],
    ]);

    $this->repo->renameGroup('renamed');

    expect(Setting::byGroup(Settings::DEFAULT_GROUP)->count())->toBe(0)
        ->and(Setting::byGroup('renamed')->count())->toBe(2)
        ->and($this->repo->getAll())
        ->name->toBe('Coyotito')
        ->debug->toBeTrue();
});
