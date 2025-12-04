<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Console\Commands;

use Coyotito\LaravelSettings\Console\Commands\GeneratorCommand as Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

use function Illuminate\Filesystem\join_paths;

class MakeSettingsMigrationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:settings-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new class for settings';

    /**
     * The type of file being generated.
     */
    protected static string $type = 'migration';

    protected ?string $group = null;

    /**
     * Handle the command
     */
    public function handle(): int
    {
        $migration = $this->getMigrationName();

        if (! $this->generateFile("$migration.php", database_path('migrations'))) {
            $this->components->error("Failed to create settings migration [$migration].");

            return self::FAILURE;
        }

        $this->components->success("Settings migration [{$this->removeTimestamp($migration)}] created successfully.");

        return self::SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    public function generateFile(string $basename, string $destination): bool
    {
        $pattern = join_paths(
            database_path('migrations'),
            '*_'.$this->removeTimestamp($basename),
        );

        if (filled(File::glob($pattern))) {
            $basename = Str::replace('.php', '', $this->removeTimestamp($basename));

            throw new RuntimeException("Migration [$basename] already exists.");
        }

        return parent::generateFile($basename, $destination);
    }


    /**
     * Get the migration name.
     */
    protected function getMigrationName(): string
    {
        $migrationName = $this->getNameArgument();

        $pattern = '/_to_(.+)_group$/';

        if (($group = Str::of($migrationName)->match($pattern))->isNotEmpty()) {
            $this->setGroup($group->toString());
        }

        return now()->format('Y_m_d_His') . "_$migrationName";
    }

    /**
     * Set the group for the migration.
     */
    protected function setGroup(string $group): void
    {
        $this->group = Str::of($group)->slug()->toString();

        $this->ensureNotReserved($this->group, 'group');
    }

    /**
     * {@inheritdoc}
     */
    protected function getGroup(): string
    {
        return $this->group ?? parent::getGroup();
    }

    /**
     * {@inheritdoc}
     */
    protected function formatName(string $name): string
    {
        return Str::of($name)->snake()->toString();
    }

    /**
     * Utility to remove the timestamp from a migration name.
     */
    protected function removeTimestamp(string $migrationName): string
    {
        return Str::of($migrationName)->replaceMatches('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '')->toString();
    }
}
