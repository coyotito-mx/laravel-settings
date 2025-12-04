# Laravel Settings

## Description

A Laravel package to manage application settings with typed classes, automatic persistence, and group support.

## Installation

You can install the package via Composer:

```bash
composer require coyotito/laravel-settings
```

Publish the migrations and config file:

```bash
php artisan vendor:publish --tag=laravel-settings-migrations
php artisan vendor:publish --tag=laravel-settings-config
php artisan migrate
```

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
> By default, the package usage Eloquent to persist the information

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

Define initial values using the Schema facade:

```php
use Coyotito\LaravelSettings\Facades\Schema;
use Coyotito\LaravelSettings\Database\Schema\Blueprint;

Schema::default(function (Blueprint $group) {
    $group->add('site_name', 'My Application');
    $group->add('debug_mode', false);
});

// For a specific group
Schema::in('billing', function (Blueprint $group) {
    $group->add('currency', 'USD');
    $group->add('tax_rate', 0.16);
});
```

### Groups

Settings are organized by groups. Define a custom group in your class:

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
