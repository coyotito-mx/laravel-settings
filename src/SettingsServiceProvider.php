<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings;

use Coyotito\LaravelSettings\Console\Commands\MakeSettingsClassCommand;
use Coyotito\LaravelSettings\Database\Schema\Builder;
use Coyotito\LaravelSettings\Repositories\Contracts\Repository;
use Coyotito\LaravelSettings\Repositories\EloquentRepository;
use Illuminate\Support\ServiceProvider;
use Coyotito\LaravelSettings\Facades\LaravelSettings;

use function Coyotito\LaravelSettings\Helpers\package_path;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            package_path('config', 'settings.php'),
            'settings',
        );

        $this->app->singleton('settings.manager', function (): LaravelSettingsManager {
            return new LaravelSettingsManager();
        });

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

        $rootNamespace = trim($this->app->getNamespace(), '\\');

        LaravelSettings::addNamespace(
            "$rootNamespace\\Settings",
            app_path('Settings'),
        );
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
                MakeSettingsClassCommand::class,
            ]);
        }

        $this->bindSettingClasses();
    }

    public function bindSettingClasses(): void
    {
        $classes = LaravelSettings::getClasses();

        foreach ($classes as $class) {
            $this->app->scoped($class, function () use ($class) {
                /**
                 * @var Settings $class
                 * @var Repository $repository
                 * */

                $repository = $this->app->make('settings.repository');

                return new $class($repository);
            });
        }
    }
}
