<?php

use Illuminate\Support\Facades\File;

use function Illuminate\Filesystem\join_paths;
use function Pest\Laravel\artisan;

beforeEach(function () {
    rmdir_recursive(app_path('Custom'));
    rmdir_recursive(app_path('Settings'));
    rmdir_recursive(database_path('migrations'), delete_root: false);
});

afterEach(function () {
    rmdir_recursive(app_path('Custom'));
    rmdir_recursive(app_path('Settings'));
    rmdir_recursive(database_path('migrations'), delete_root: false);
});

it('can generate default migration and class', function () {
    $classFile = join_paths(app_path('Settings'), 'DefaultSettings.php');
    $migrationFiles = join_paths(database_path('migrations'), '*_add_settings_to_default_group.php');

    expect('App\\Settings\\DefaultSettings')
        ->not->toBeClassSettings()
        ->and(File::glob($migrationFiles))
        ->toBeEmpty();

    artisan('make:settings')
        ->expectsOutputToContain("Settings class [DefaultSettings] created successfully.")
        ->expectsOutputToContain("Settings migration [add_settings_to_default_group] created successfully.")
        ->assertSuccessful();

    expect('App\\Settings\\DefaultSettings')
        ->toBeClassSettings()
        ->and($classFile)
        ->toBeFile()
        ->and(File::glob($migrationFiles)[0])
        ->toBeFile();
});

it('can create settings class with different name', function () {
    expect('App\\Settings\\General')
        ->not->toBeClassSettings();

    artisan('make:settings', ['class' => 'General'])
        ->expectsOutputToContain("Settings class [General] created successfully.")
        ->expectsOutputToContain("Settings migration [add_settings_to_default_group] created successfully.")
        ->assertSuccessful();

    expect('App\\Settings\\General')
        ->toBeClassSettings();

    $classFile = join_paths(app_path('Settings'), 'General.php');
    $migrationFile = glob(join_paths(database_path('migrations'), '*_add_settings_to_default_group.php'))[0];

    expect($classFile)
        ->toBeFile()
        ->and($migrationFile)
        ->toBeFile();
});

it('create setting class in specified namespace', function () {
    expect('App\\Custom\\Settings\\DefaultSettings')
        ->not->toBeClassSettings();

    artisan('make:settings', ['--namespace' => 'App\\Custom\\Settings', '--without-migration' => true])
        ->expectsOutputToContain("Settings class [DefaultSettings] created successfully.")
        ->assertSuccessful();

    expect('App\\Custom\\Settings\\DefaultSettings')
        ->toBeClassSettings();
});

it('only generates the migration file', function () {
    $migrationFile = join_paths(database_path('migrations'), '*_add_settings_to_default_group.php');

    artisan('make:settings', ['--without-class' => true])
        ->doesntExpectOutputToContain("Settings class [DefaultSettings] created successfully.")
        ->expectsOutputToContain("Settings migration [add_settings_to_default_group] created successfully.")
        ->assertSuccessful();

    expect('App\\Settings\\DefaultSettings')
        ->not->toBeClassSettings()
        ->and(File::glob($migrationFile)[0])
        ->toBeFile();
});

it('only generates the settings class', function () {
    artisan('make:settings', ['--without-migration' => true])
        ->doesntExpectOutputToContain("Settings migration [add_settings_to_default_group] created successfully.")
        ->expectsOutputToContain("Settings class [DefaultSettings] created successfully.")
        ->assertSuccessful();

    expect('App\\Settings\\DefaultSettings')
        ->toBeClassSettings()
        ->and(File::files(database_path('migrations')))
        ->toBeEmpty();
});

it('generate setting class even when both are specified to be skipped', function () {
    artisan('make:settings', ['--without-migration' => true, '--without-class' => true])
        ->doesntExpectOutputToContain("Settings migration [add_settings_to_default_group] created successfully.")
        ->expectsOutputToContain("Settings class [DefaultSettings] created successfully.")
        ->assertSuccessful();

    expect('App\\Settings\\DefaultSettings')
        ->toBeClassSettings();

    $pattern = database_path(join_paths('migrations', '*_create_default_settings.php'));

    expect(glob($pattern))
        ->toBeEmpty()
        ->toHaveCount(0);
});

it('can create settings for a custom group', function () {
    $classFile = join_paths(app_path('Settings'), 'Billing.php');
    $migrationFile = join_paths(database_path('migrations'), '*_add_settings_to_billing_group.php');

    expect('App\\Settings\\Billing')
        ->not->toBeClassSettings();

    artisan('make:settings', ['--group' => 'billing'])
        ->expectsOutputToContain("Settings class [Billing] created successfully.")
        ->expectsOutputToContain("Settings migration [add_settings_to_billing_group] created successfully.")
        ->assertSuccessful();

    expect('App\\Settings\\Billing')
        ->toBeClassSettings();

    expect($migration = File::glob($migrationFile)[0])
        ->toBeFile()
        ->and($classFile)
        ->toBeFile()
        ->and(File::get($classFile))
        ->toBe(
            <<<'PHP'
        <?php

        declare(strict_types=1);

        namespace App\Settings;

        use Coyotito\LaravelSettings\Settings;

        class Billing extends Settings
        {
            /**
             * Get the group name
             */
            #[\Override]
            public static function getGroup(): string
            {
                return 'billing';
            }
        }

        PHP
        )
        ->and(File::get($migration))
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
