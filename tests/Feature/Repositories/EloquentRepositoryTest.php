<?php

use Coyotito\LaravelSettings\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Pest\Expectation;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    /** @var \Coyotito\LaravelSettings\Repositories\Contracts\Repository $repo */
    $repo = app()->make('settings.repository');
    $repo->setGroup('default');

    $this->repo = $repo;
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

    assertDatabaseHas($table, [
        'name' => 'name',
        'payload' => json_encode('Coyotito'),
        'group' => 'default',
    ]);
    assertDatabaseHas($table, [
        'name' => 'debug',
        'payload' => json_encode(true),
        'group' => 'default',
    ]);
});

it('can fill properties', function () {
    Setting::insert([
        ['group' => 'default', 'name' => 'name', 'payload' => json_encode('Coyotito')],
        ['group' => 'default', 'name' => 'description', 'payload' => json_encode('Lorem ipsum dolor it')],
    ]);

    $defaultSettings = new class ($this->repo) extends \Coyotito\LaravelSettings\Settings {
        public string $name;
        public string $description;
    };

    expect($defaultSettings)
        ->name
        ->toBe('Coyotito')
        ->description
        ->toBe('Lorem ipsum dolor it');
});

it('update settings', function () {
    Setting::insert([
        ['group' => 'default', 'name' => 'debug', 'payload' => json_encode(true)],
        ['group' => 'default', 'name' => 'config', 'payload' => json_encode(['name' => 'Coyotito', 'description' => 'Lorem ipsum dolor it'])],
    ]);

    $defaultSettings = new class ($this->repo) extends \Coyotito\LaravelSettings\Settings {
        public bool $debug;
        public array $config;
    };

    tap($defaultSettings, function ($settings) {
        $settings->debug = false;
        $settings->config['name'] = 'Coyotito Rocks!';
    })->save();

    expect([
        (object) $this->repo->getAll(),
        $defaultSettings,
    ])
        ->each(function (Expectation $obj) {
            $obj
                ->debug
                ->toBeFalse()
                ->config
                ->toBe([
                    'name' => 'Coyotito Rocks!',
                    'description' => 'Lorem ipsum dolor it',
                ]);
        });
});

test('dynamic properties are not persisted', function () {
    Setting::insert([
        ['group' => 'default', 'name' => 'name', 'payload' => json_encode('Coyotito')],
    ]);

    $defaultSettings = new class ($this->repo) extends \Coyotito\LaravelSettings\Settings {
        public string $dynamic = 'Hello, world!';
        public string $name;
    };

    expect($defaultSettings)
        ->dynamic->toBe('Hello, world!')
        ->name->toBe('Coyotito');

    tap($defaultSettings, function ($settings) {
        $settings->dynamic = 'Lorem ipsum';
        $settings->name = 'Hello, World!';
    })->save();

    expect($this->repo)
        ->get('dynamic')
        ->toBeNull()
        ->get('name')
        ->toBe('Hello, World!')
        ->getAll()
        ->not->toContain('Lorem ipsum')
        ->toContain('Hello, World!');
});
