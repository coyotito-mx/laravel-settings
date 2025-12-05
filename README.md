
<h1 align="center">Laravel Settings</h1>

<p align="center">
<img alt="Static Badge" src="https://img.shields.io/badge/PHP-%E2%89%A58.4-%234F5D95">
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/coyotito/laravel-settings" alt="Latest Stable Version"></a>
<a href="https://github.com/coyotito-mx/laravel-settings/actions/workflows/tests.yml"><img src="https://github.com/coyotito-mx/laravel-settings/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://github.com/coyotito-mx/laravel-settings/actions/workflows/lint.yml"><img src="https://github.com/coyotito-mx/laravel-settings/actions/workflows/lint.yml/badge.svg" alt="Build Status"></a>
</p>

<p align="center">A small package to define typed settings classes and persist application configuration values grouped by logical namespaces.</p>

---

## Installation

Install with Composer:

```bash
composer require coyotito/laravel-settings
```

Publish configuration (optional):

```bash
php artisan vendor:publish --tag=laravel-settings-config
```

Publish migrations (the package ships a migration stub):

```bash
php artisan vendor:publish --tag=laravel-settings-migrations
php artisan migrate
```

---

## Configuration

The package publishes `config/settings.php`. Defaults:

```php
return [
    'repository' => 'eloquent',

    'repositories' => [
        'eloquent' => [
            'class' => \Coyotito\LaravelSettings\Repositories\EloquentRepository::class,
            'model' => \Coyotito\LaravelSettings\Models\Setting::class,
        ],
    ],
];
```

- `repository` selects which repository implementation to use (`eloquent` by default).
- You may add your own repository implementations and update the config accordingly.

To change the Eloquent model used for storage, update `repositories.eloquent.model` in the config.

---

## Repositories

Built-in repositories:
- `EloquentRepository` — stores settings in the database using the `Setting` model. (Default)
- `InMemoryRepository` — stores settings in memory (useful for tests).

Repository contracts live in `src/Repositories/Contracts` and the base logic is in `src/Repositories/BaseRepository.php`.

---

## Creating Settings Classes

Generate a settings class (and optional migration):

```bash
php artisan make:settings MySettings
```

This will use the stubs in `stubs/` and create a class under `App\Settings` by default (see `make:settings-class`).

A settings class is simply a class that extends `Coyotito\LaravelSettings\Settings` and exposes typed public properties:

```php
<?php

namespace App\Settings;

use Coyotito\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name = '';

    public bool $debug_mode = false;

    public ?array $allowed_ips = null; // nullable supported
}
```

Important: the package inspects public properties and their declared types. The supported scalar types are:
- `int`, `float`, `bool`, `string`, `array`

Notes on types and casting:
- If a property has no type declaration the raw value is used.
- Nullable types are supported (e.g. `?string`). When the stored value is the string `'null'` or an empty string, it will be converted to `null` if the property allows null.
- Union and intersection types are explicitly not supported and will throw `InvalidArgumentException` when encountered.

The class group is resolved via the static method `getGroup()` on the settings class (defaults to `default`). If you want a custom group, override this method:

```php
class BillingSettings extends Settings
{
    public static function getGroup(): string
    {
        return 'billing';
    }
}
```
---

## Migrations

To start persisting the information, create a migration to add the settings you want in your app. Generate a migration
calling the command `make:settings migration="Add settings`. Doing this will create a migration file like this `*_add_settings.php` (you can name it as you want)
and the file should look like the following code.

```php
declare(strict_types=1);


use Illuminate\Database\Migrations\Migration;
use Coyotito\LaravelSettings\Facades\Schema;
use Coyotito\LaravelSettings\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::default(function (Blueprint $group) {
            // Add your settings here
        });
    }

    public function down(): void
    {
        Schema::delete([
            // Add your settings here to be deleted
        ]);
    }
};
```

As you can see, this is a standard Laravel migration file, but instead of using the `Schema` facade from Laravel,
we use the `Coyotito\LaravelSettings\Facades\Schema` facade to add or delete settings.

---

## Using Settings

You can resolve your settings class via the container or type-hint it for dependency injection.

Via DI or container:

```php
use App\Settings\GeneralSettings;

public function index(GeneralSettings $settings)
{
    // read
    $siteName = $settings->site_name;

    // write and persist
    $settings->site_name = 'My Site';
    $settings->save();
}

// or
$settings = app(GeneralSettings::class);
```

Settings in the container are bound by group and scoped. The package's `SettingsManager` registers settings classes it finds under the `App\Settings` namespace (see `SettingsServiceProvider`).

### Helper: `settings()`

A global helper is provided at `src/helpers.php`. Usage examples:

```php
// Get single value from default group
$value = settings('site_name');

// Get a value with default
$value = settings('site_name', 'default');

// Get multiple values from default group
$values = settings(['site_name', 'debug_mode']);

// Set multiple values in default group
settings(['site_name' => 'My Site', 'debug_mode' => true]);

// Group-specific get
$values = settings('billing', ['currency', 'tax_rate']);

// Group-specific set
settings('billing', ['currency' => 'EUR']);

// Get the service instance
$service = settings(); // returns \Coyotito\LaravelSettings\SettingsService
```

Helper rules (summary):
- If first arg is `null`, returns the `SettingsService` instance.
- If first arg is an associative array, it performs a set on the default group.
- If first arg is an array list (list of names) it performs a get on the default group.
- If first arg is a string and second arg is an array:
  - If second arg is a list (array_is_list), the second arg is treated as the list of keys to get from that group.
  - Otherwise the second arg is treated as an associative array of keys/values to set in that group.

---

## Generators / Commands

- `php artisan make:settings {class?} {migration?} {--g|--group=}` — convenience command that can scaffold both a class and a migration.
- `php artisan make:settings-class` — create a settings class (uses `stubs/class-default.stub` or `stubs/class-group.stub`).
- `php artisan make:settings-migration` — scaffold a migration that uses the package `Schema` facade and `Database\Schema\Blueprint` (uses migration stubs in `stubs/`).

Stubs are available under the package `stubs/` directory and the generator will pick the `group` variant when the group is not `default`.

---

## Testing

Run tests using the provided composer script:

```bash
composer run test
```

Tests are located in the `tests/` directory and use Pest + Orchestra Testbench.

---

## Troubleshooting

- Namespace resolution for settings classes relies on Composer's PSR-4 autoload map. If your settings namespace is not being discovered, make sure it is declared in `composer.json` `autoload.psr-4` and run `composer dump-autoload`.
- If generators fail to resolve a namespace path, check the `psr4_namespace_to_path()` helper and ensure your app's `vendor/composer/autoload_psr4.php` contains the expected mapping (the package uses this to map `App\\Settings` to a directory).
- If your migrations are not published, ensure you're using the correct publish tag:
  - `laravel-settings-config` for config files
  - `laravel-settings-migrations` for the migration stubs shipped by the package

---

## Customization

- Customize stubs (`stubs/`) if you want different scaffolds for classes or migrations.
- Add your own repository implementation by implementing `Coyotito\LaravelSettings\Repositories\Contracts\Repository` and registering it in `config/settings.php`.

---

## Contributing

See `CONTRIBUTING.md` for details about contributing, tests and code style.

---

## License

MIT — see `LICENSE.md`.
