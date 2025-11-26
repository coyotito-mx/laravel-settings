<?php

use function Illuminate\Filesystem\join_paths;
use function Pest\Laravel\artisan;

beforeEach(function () {
    rmdir_recursive(database_path('migrations'), delete_root: false);
    rmdir_recursive(app_path('Settings'));
});

afterEach(function () {
    rmdir_recursive(database_path('migrations'), delete_root: false);
    rmdir_recursive(app_path('Settings'));
});

it('can generate default migration', function () {
    expect('App\\Settings\\DefaultSettings')
        ->not->toBeClassSettings();

    artisan('make:settings')
        ->expectsOutputToContain('Migration for [default] group created')
        ->assertSuccessful();

    expect('App\\Settings\\DefaultSettings')
        ->toBeClassSettings();

    $classFile = join_paths(app_path('Settings'), 'DefaultSettings.php');
    $migrationFile = glob(join_paths(database_path('migrations'), '*_create_default_settings.php'))[0];

    expect($migrationFile)
        ->toBeFile()
        ->and(file_get_contents($classFile))
        ->toBe(<<<'PHP'
        <?php

        declare(strict_types=1);

        namespace App\Settings;

        use Coyotito\LaravelSettings\Settings;

        class DefaultSettings extends Settings
        {
            // Add your typed settings (properties)
        }

        PHP)
        ->and(file_get_contents($migrationFile))
        ->toBe(<<<'PHP'
        <?php

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
                // Remove your settings here
            }
        };

        PHP);
});

it('can create settings with different class name', function () {
    expect('App\\Settings\\General')
        ->not->toBeClassSettings();

    artisan('make:settings', ['--class-name' => 'General'])
        ->assertSuccessful();

    expect('App\\Settings\\General')
        ->toBeClassSettings();

    $classFile = join_paths(app_path('Settings'), 'General.php');

    expect($classFile)
        ->and(file_get_contents($classFile))
        ->toBe(<<<'PHP'
        <?php

        declare(strict_types=1);

        namespace App\Settings;

        use Coyotito\LaravelSettings\Settings;

        class General extends Settings
        {
            // Add your typed settings (properties)
        }

        PHP);
});

it('cannot replace already created migration', function () {
    artisan('make:settings')->assertSuccessful();

    expect(fn () => artisan('make:settings'))
        ->toThrow('The migration [default] settings already exists');
});

it('only generates the migration file', function () {
    artisan('make:settings', ['--without-class' => true])
        ->expectsOutputToContain('Migration for [default] group created')
        ->assertSuccessful();

    expect('App\\Settings\\DefaultSettings')
        ->not->toBeClassSettings();
});

it('only generates the settings class', function () {
    artisan('make:settings', ['--without-migration' => true])
        ->doesntExpectOutputToContain('Migration for [default] group created')
        ->expectsOutputToContain('Class [DefaultSettings] for group [default]')
        ->assertSuccessful();

    expect('App\\Settings\\DefaultSettings')
        ->toBeClassSettings();

    $pattern = database_path(join_paths('migrations', '*_create_default_settings.php'));

    expect(glob($pattern))
        ->toBeEmpty()
        ->toHaveCount(0);
});

it('generate setting class even when both are specified to be skipped', function () {
    artisan('make:settings', ['--without-migration' => true, '--without-class' => true])
        ->doesntExpectOutputToContain('Migration for [default] group created')
        ->expectsOutputToContain('Class [DefaultSettings] for group [default]')
        ->assertSuccessful();

    expect('App\\Settings\\DefaultSettings')
        ->toBeClassSettings();

    $pattern = database_path(join_paths('migrations', '*_create_default_settings.php'));

    expect(glob($pattern))
        ->toBeEmpty()
        ->toHaveCount(0);
});

it('creates a migration file for the default group', function () {
    artisan('make:settings')->assertSuccessful();

    $files = glob(database_path('migrations'.DIRECTORY_SEPARATOR.'*_create_default_settings.php'));

    expect($files)->not->toBeEmpty()
        ->and($files)->toHaveCount(1);
});

it('can create settings for a custom group', function () {
    expect('App\\Settings\\Billing')
        ->not->toBeClassSettings();

    artisan('make:settings', ['--group' => 'billing'])
        ->expectsOutputToContain('Migration for [billing] group created')
        ->assertSuccessful();

    expect('App\\Settings\\Billing')
        ->toBeClassSettings();

    $files = glob(database_path('migrations'.DIRECTORY_SEPARATOR.'*_create_billing_settings.php'));

    expect($files)->not->toBeEmpty()
        ->and($files)->toHaveCount(1);

    $classFile = join_paths(app_path('Settings'), 'Billing.php');
    $migrationFile = glob(join_paths(database_path('migrations'), '*_create_billing_settings.php'))[0];

    expect($migrationFile)
        ->toBeFile()
        ->and(file_get_contents($classFile))
        ->toBe(<<<'PHP'
        <?php

        declare(strict_types=1);

        namespace App\Settings;

        use Coyotito\LaravelSettings\Settings;

        class Billing extends Settings
        {
            /**
             * Group name
             */
            public static function group(): string
            {
                return 'billing';
            }
        }

        PHP)
        ->and(file_get_contents($migrationFile))
        ->toBe(<<<'PHP'
        <?php

        declare(strict_types=1);


        use Illuminate\Database\Migrations\Migration;
        use Coyotito\LaravelSettings\Facades\Schema;
        use Coyotito\LaravelSettings\Database\Schema\Blueprint;

        return new class extends Migration
        {
            public function up(): void
            {
                Schema::in('billing', function (Blueprint $group) {
                    // Add your settings here
                });
            }

            public function down(): void
            {
                // Remove your settings here
            }
        };

        PHP);
});

it('can create settings class with different name than the group', function () {
    expect('App\\Settings\\SiteSettings')
        ->not->toBeClassSettings();

    artisan('make:settings', ['--class-name' => 'SiteSettings'])
        ->expectsOutputToContain('Migration for [default] group created')
        ->assertSuccessful();

    expect('App\\Settings\\SiteSettings')
        ->toBeClassSettings();

    $files = glob(database_path('migrations'.DIRECTORY_SEPARATOR.'*_create_default_settings.php'));

    expect($files)->not->toBeEmpty()
        ->and($files)->toHaveCount(1);
});

it('cannot create group with reserved words', function () {
    expect(function () {
        artisan('make:settings', ['--group' => 'settings']);
    })->toThrow(RuntimeException::class, "The provided name [settings] is reserved");

    $files = glob(database_path('migrations'.DIRECTORY_SEPARATOR.'*_create_settings_settings.php'));

    expect($files)->toBeEmpty();

    expect('App\\Settings\\Settings')
        ->not->toBeClassSettings();
});
