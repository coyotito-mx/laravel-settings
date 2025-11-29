<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Concerns\PromptsForMissingInput as ConcernsPromptsForMissingInput;

class MakeSettingsCommand extends Command
{
    use ConcernsPromptsForMissingInput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:settings
                            { class? : The name of the settings class }
                            { migration? : The name of the settings migration }
                            { --g|group=default : The name of the group }
                            { --namespace= : The namespace of the settings class }
                            { --without-class : Without the class settings }
                            { --without-migration : Without migration settings }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new class and migration for settings';

    /**
     * Handle the command
     */
    public function handle(): int
    {
        if ($migration = $this->shouldCreateMigration()) {
            $this->generateMigration();
        }

        if (! $migration || $this->shouldCreateClass()) {
            $this->generateClass();
        }

        return self::SUCCESS;
    }

    /**
     * Check if we should need to create the Settings class
     */
    protected function shouldCreateClass(): bool
    {
        return ! $this->option('without-class');
    }

    /**
     * Check if we should need to create the Settings migration
     */
    protected function shouldCreateMigration(): bool
    {
        return ! $this->option('without-migration');
    }

    /**
     * Generate the Settings migration
     */
    protected function generateMigration(): int
    {
        return $this->call(
            command: 'make:settings-migration',
            arguments: $this->getMigrationGenerationArguments(),
        );
    }

    /**
     * Generate the Settings class
     */
    protected function generateClass(): int
    {
        return $this->call(
            command: 'make:settings-class',
            arguments: $this->getClassGenerationArguments(),
        );
    }

    /**
     * Get the Settings class name
     */
    protected function getClassName(): string
    {
        if ($className = $this->argument('class')) {
            return $className;
        }

        return $this->option('group') === \Coyotito\LaravelSettings\Settings::DEFAULT_GROUP ? 'DefaultSettings' : $this->option('group');
    }

    /**
     * Get the Settings migration name
     */
    protected function getMigrationName(): string
    {
        if ($migrationName = $this->argument('migration')) {
            return $migrationName;
        }

        $group = $this->option('group');

        return "add_settings_to_{$group}_group";
    }

    /**
     * Get the common arguments for the commands
     */
    protected function getArgumentsForCommands(): array
    {
        $arguments = [];

        if ($this->option('group')) {
            $arguments['--group'] = $this->option('group');
        }

        return $arguments;
    }

    protected function getClassGenerationArguments(): array
    {
        $arguments = $this->getArgumentsForCommands();

        $arguments['name'] = $this->getClassName();

        if ($this->option('namespace')) {
            $arguments['--namespace'] = $this->option('namespace');
        }

        return $arguments;
    }

    protected function getMigrationGenerationArguments(): array
    {
        $arguments = $this->getArgumentsForCommands();

        $arguments['name'] = $this->getMigrationName();

        return $arguments;
    }
}
