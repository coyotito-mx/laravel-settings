# Laravel Settings

## Description

A Laravel package to manage application settings with typed classes, automatic persistence, and group support.

## Installation

You can install the package via Composer:

```bash
composer require coyotito/laravel-settings
```

Publish the migrations

```bash
php artisan vendor:publish --tag=laravel-settings-migrations
php artisan migrate
```

> **Note**
>
> By default, the package usage Eloquent to persist the information

## Usage

### Creating a Settings Class

Generate a settings class and migration:

```bash
php artisan make:settings GeneralSettings
```

Or with a custom group:

```bash
php artisan make:settings --group=billing
```

### Defining Settings

Add typed public properties to your settings class

> **Note**
>
> The only available types are:
> `int`,
> `float`,
> `string`,
> `array`,
> and `bool`.
> But, if you try to store other type of value you "can", depending on the storage repository,
> but when the settings class is hydrated, an exception will be thrown.

```php
<?php

namespace App\Settings;

use Coyotito\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name = '';

    public bool $debug_mode = false;

    public ?array $allowed_ips = null;
}
```

### Using Settings

Resolve settings via dependency injection or the container:

```php
use App\Settings\GeneralSettings;

// Via dependency injection
public function method(GeneralSettings $settings)
{
    return $settings->site_name;
}

// Via container
$settings = app(GeneralSettings::class);
$settings->site_name = 'My Site';
$settings->save();
```

### Settings Migrations

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

### Groups

Settings are organized by groups. Define a group name by returning the group name in the static function `Settings::group()`

```php
class BillingSettings extends Settings
{
    public string $currency = 'USD';

    public float $tax_rate = 0.0;

    public static function group(): string
    {
        return 'billing';
    }
}
```

## Testing

To run the tests, run the following command:

```bash
composer run test
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

Please see [LICENSE.md](LICENSE.md) for details.
