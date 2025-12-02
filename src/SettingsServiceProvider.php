<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use Coyotito\LaravelSettings\Console\Commands\MakeSettingsClassCommand;
use Coyotito\LaravelSettings\Console\Commands\MakeSettingsCommand;
use Coyotito\LaravelSettings\Console\Commands\MakeSettingsMigrationCommand;
use Coyotito\LaravelSettings\Database\Schema\Builder;
use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use Coyotito\LaravelSettings\Repositories\EloquentRepository;
use Coyotito\LaravelSettings\Repositories\InMemoryRepository;
use File;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

use function Coyotito\LaravelSettings\Helpers\package_path;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->publishConfig();

        $this->registerBindings();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishMigrations();

        $this->addCommands([
            MakeSettingsCommand::class,
            MakeSettingsClassCommand::class,
            MakeSettingsMigrationCommand::class,
        ]);

        $this->bindSettingClasses();

        $rootNamespace = trim($this->app->getNamespace(), '\\');

        Facades\SettingsManager::addNamespace("$rootNamespace\\Settings");
    }

    /**
     * Register the package bindings.
     */
    protected function registerBindings(): void
    {
        $this->app->bind('repository.eloquent', function (): EloquentRepository {
            $model = config('settings.repositories.eloquent.model');

            return new EloquentRepository($model);
        });

        $this->app->bind('repository.in-memory', function (): InMemoryRepository {
            return new InMemoryRepository();
        });

        $this->app->bind('settings.schema', function (): Builder {
            $repo = $this->app->make('settings.repository');

            return new Builder($repo);
        });

        $this->app->bind(Repository::class, function (): Repository {
            $repo = config('settings.repository');

            return $this->app->make("repository.$repo");
        });

        $this->app->alias(Repository::class, 'settings.repository');

        $this->app->scoped('settings.manager', function (Application $app): SettingsManager {
            return new SettingsManager($app);
        });

        $this->app->scoped(Settings::class, function (Application $app): Settings {
            $manager = $app->make('settings.manager');

            return new Settings($manager);
        });

        $this->app->alias(Settings::class, 'settings.service');
    }

    /**
     * Add commands to the application.
     *
     * This commands will only be registered if the application is running in the console.
     */
    protected function addCommands(array $commands = []): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands($commands);
        }
    }

    /**
     * Publish the package configuration files.
     */
    protected function publishConfig(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $files = File::glob(package_path('config', '*.php'));

        if (blank($files)) {
            return;
        }

        collect($files)->each(function (string $filepath): void {
            $filename = basename($filepath);

            $this->mergeConfigFrom($filepath, basename($filename, '.php'));

            $this->publishes([
                $filepath => config_path($filename),
            ], 'laravel-settings-config');
        });
    }

    protected function publishMigrations(): void
    {
        $migrationFilename = 'eloquent_repository_migration.php';

        $this->publishesMigrations([
            package_path('database', 'migrations', $migrationFilename) => database_path(
                implode(DIRECTORY_SEPARATOR, [
                    'migrations',
                    now()->format('Y_m_d_His_') . 'create_settings_table.php',
                ]),
            ),
        ], 'laravel-settings-migrations');
    }

    public function bindSettingClasses(): void
    {
        Facades\SettingsManager::clearResolvedSettings();

        Facades\SettingsManager::getClasses();
    }
}
