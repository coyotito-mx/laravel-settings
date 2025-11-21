<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use Coyotito\LaravelSettings\Console\Commands\MakeSettingsMigration;
use Coyotito\LaravelSettings\Database\Schema\Builder;
use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use Coyotito\LaravelSettings\Repositories\EloquentRepository;
use Illuminate\Support\ServiceProvider;

use function Coyotito\LaravelSettings\Helpers\package_path;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            package_path('config', 'settings.php'),
            'settings',
        );

        $this->app->bind('settings.repository', function (): Repository {
            return new EloquentRepository(
                model: config('settings.model'),
            );
        });

        $this->app->bind('settings.schema', function (): Builder {
            return new Builder(
                repo: $this->app->make('settings.repository'),
            );
        });
    }

    public function boot(): void
    {
        // Publish config file
        $this->publishes([
            package_path('config', 'settings.php') => config_path('settings.php'),
        ], 'laravel-settings-config');

        $migrationFilename = 'eloquent_repository_migration.php';

        $this->publishesMigrations([
            package_path('database', 'migrations', $migrationFilename) => database_path(
                implode(DIRECTORY_SEPARATOR, [
                    'migrations',
                    now()->format('y_m_d_his_') . $migrationFilename,
                ]),
            ),
        ], 'laravel-settings-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeSettingsMigration::class,
            ]);
        }

        $this->bindSettingClasses();
    }

    public function bindSettingClasses(): void
    {
        $classes = config('settings.classes');

        foreach ($classes as $class) {
            if (! is_subclass_of($class, Settings::class)) {
                continue;
            }

            $this->app->scoped($class, function ($app) use ($class) {
                $repository = $app->make('settings.repository');

                return new $class($repository);
            });
        }
    }
}
