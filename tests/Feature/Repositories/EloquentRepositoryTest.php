<?php

use Coyotito\LaravelSettings\Models\Setting;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    rmdir_recursive(database_path('migrations'), delete_root: false);

    /** @var \Coyotito\LaravelSettings\Repositories\Contracts\Repository $repo */
    $repo = app()->make('settings.repository');
    $repo->setGroup('default');

    artisan('vendor:publish --tag=laravel-settings-migrations');
    artisan('migrate');

    $this->repo = $repo;
});

afterEach(function () {
    rmdir_recursive(database_path('migrations'), delete_root: false);
});

it('has settings table', function () {
    $table = (new Setting())->getTable();

    expect(Schema::hasTable($table))->toBeTrue();
});

it('has seeded settings', function () {
    $table = (new Setting())->getTable();

    Setting::insert([
        ['group' => 'default', 'name' => 'name', 'payload' => json_encode('Coyotito')],
        ['group' => 'default', 'name' => 'debug', 'payload' => json_encode(true)],
    ]);

    assertDatabaseCount($table, 2);

    $settings = (object) $this->repo->getAll();

    expect($settings)
        ->name->toBe('Coyotito')
        ->debug->toBeTrue();
});

it('can fill properties', function () {
    Setting::insert([
        ['group' => 'default', 'name' => 'name', 'payload' => json_encode('Coyotito')],
        ['group' => 'default', 'name' => 'description', 'payload' => json_encode('Lorem ipsum dolor it')],
    ]);

    $settings = (object) $this->repo->get(['name', 'description']);

    expect($settings)
        ->name->toBe('Coyotito')
        ->description->toBe('Lorem ipsum dolor it');
});

it('update settings', function () {
    Setting::insert([
        ['group' => 'default', 'name' => 'debug', 'payload' => json_encode(true)],
        ['group' => 'default', 'name' => 'config', 'payload' => json_encode(['name' => 'Coyotito', 'description' => 'Lorem ipsum dolor it'])],
    ]);

    $this->repo->update([
        'debug' => false,
        'config' => [
            'name' => 'Coyotito Rocks!',
            'description' => 'Lorem ipsum dolor it',
        ],
    ]);

    $settings = (object) $this->repo->getAll();

    expect($settings)
        ->debug->toBeFalse()
        ->config->toBe([
            'name' => 'Coyotito Rocks!',
            'description' => 'Lorem ipsum dolor it',
        ]);
});

test('dynamic properties are not persisted', function () {
    Setting::insert([
        ['group' => 'default', 'name' => 'name', 'payload' => json_encode('Coyotito')],
    ]);

    // Local "dynamic" data not managed by the repository
    $local = (object) [
        'dynamic' => 'Hello, world!',
        'name' => $this->repo->get('name'),
    ];

    expect($local)
        ->dynamic->toBe('Hello, world!')
        ->name->toBe('Coyotito');

    // Mutate local data and persist only the relevant setting using the repository
    $local->dynamic = 'Lorem ipsum';
    $this->repo->update('name', 'Hello, World!');

    expect($this->repo)
        ->get('dynamic')
        ->toBeNull()
        ->get('name')
        ->toBe('Hello, World!')
        ->getAll()
        ->not->toContain('Lorem ipsum')
        ->toContain('Hello, World!');
});

it('gets a single setting with default when not found', function () {
    $value = $this->repo->get('non_existing', 'fallback');

    expect($value)->toBe('fallback')
        ->and(Setting::query()->where('group', 'default')->count())->toBe(0);
});

it('gets multiple settings as associative array', function () {
    Setting::insert([
        ['group' => 'default', 'name' => 'name', 'payload' => json_encode('Coyotito')],
        ['group' => 'default', 'name' => 'debug', 'payload' => json_encode(true)],
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
        'group' => 'default',
        'name' => 'name',
        'payload' => json_encode('Coyotito'),
    ]);

    assertDatabaseHas($table, [
        'group' => 'default',
        'name' => 'debug',
        'payload' => json_encode(true),
    ]);

    assertDatabaseHas($table, [
        'group' => 'default',
        'name' => 'env',
        'payload' => json_encode('local'),
    ]);
});

it('updates existing settings', function () {
    Setting::insert([
        ['group' => 'default', 'name' => 'name', 'payload' => json_encode('Coyotito')],
        ['group' => 'default', 'name' => 'debug', 'payload' => json_encode(true)],
    ]);

    $this->repo->update('name', 'Coyotito Rocks!');
    $this->repo->update([
        'debug' => false,
    ]);

    $all = $this->repo->getAll();

    expect((object) $all)
        ->name->toBe('Coyotito Rocks!')
        ->debug->toBeFalse();
});

it('deletes single and multiple settings and returns affected count', function () {
    Setting::insert([
        ['group' => 'default', 'name' => 'first', 'payload' => json_encode(1)],
        ['group' => 'default', 'name' => 'second', 'payload' => json_encode(2)],
        ['group' => 'default', 'name' => 'third', 'payload' => json_encode(3)],
    ]);

    $deleted = $this->repo->deleTe('first');
    expect($deleted)->toBe(1);

    $deleted = $this->repo->delete(['second', 'missing']);
    expect($deleted)->toBe(1);

    expect(Setting::query()->where('group', 'default')->count())->toBe(1);
});

it('drops all settings for current group only', function () {
    Setting::insert([
        ['group' => 'default', 'name' => 'name', 'payload' => json_encode('Coyotito')],
        ['group' => 'default', 'name' => 'debug', 'payload' => json_encode(true)],
        ['group' => 'other', 'name' => 'name', 'payload' => json_encode('Other')],
    ]);

    $this->repo->drop();

    expect(Setting::query()->where('group', 'default')->count())->toBe(0)
        ->and(Setting::query()->where('group', 'other')->count())->toBe(1);
});

it('changes group context with setGroup and group', function () {
    Setting::insert([
        ['group' => 'default', 'name' => 'name', 'payload' => json_encode('Default')],
        ['group' => 'other', 'name' => 'name', 'payload' => json_encode('Other')],
    ]);

    expect($this->repo->group())->toBe('default');
    expect($this->repo->get('name'))->toBe('Default');

    $this->repo->setGroup('other');

    expect($this->repo->group())->toBe('other')
        ->and($this->repo->get('name'))->toBe('Other');
});

it('renames group and moves all settings to the new group', function () {
    Setting::insert([
        ['group' => 'default', 'name' => 'name', 'payload' => json_encode('Coyotito')],
        ['group' => 'default', 'name' => 'debug', 'payload' => json_encode(true)],
    ]);

    $this->repo->renameGroup('renamed');

    expect($this->repo->group())->toBe('renamed');

    expect(Setting::query()->where('group', 'default')->count())->toBe(0)
        ->and(Setting::query()->where('group', 'renamed')->count())->toBe(2);

    $all = $this->repo->getAll();

    expect((object) $all)
        ->name->toBe('Coyotito')
        ->debug->toBeTrue();
});
