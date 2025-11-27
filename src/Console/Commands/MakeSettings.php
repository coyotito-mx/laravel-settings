<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Illuminate\Support\Str;

use function Coyotito\LaravelSettings\Helpers\package_path;

class MakeSettingsMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:settings
                            { --g|group=default : The name of the group }
                            { --c|class-name= : The name of the settings class }
                            { --without-class : Without the class settings }
                            { --without-migration : Without migration settings }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new class and migration for settings';

    protected $reservedNames = [
        'settings',
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'enum',
        'eval',
        'exit',
        'extends',
        'false',
        'final',
        'finally',
        'fn',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'match',
        'namespace',
        'new',
        'or',
        'parent',
        'print',
        'private',
        'protected',
        'public',
        'readonly',
        'require',
        'require_once',
        'return',
        'self',
        'static',
        'switch',
        'throw',
        'trait',
        'true',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'yield',
        '__CLASS__',
        '__DIR__',
        '__FILE__',
        '__FUNCTION__',
        '__LINE__',
        '__METHOD__',
        '__NAMESPACE__',
        '__TRAIT__',
    ];

    public function __construct(protected Filesystem $fs)
    {
        parent::__construct();
    }

    /**
     * Handle the command
     */
    public function handle(): int
    {
        if ($migration = $this->shouldCreateMigration()) {
            $this->generateMigration();

            $this->components->success("Migration for [{$this->getGroup()}] group created");
        }

        if (! $migration || $this->shouldCreateClass()) {
            $this->ensureSettingsDirectoryExists();

            $this->generateClass();

            $this->components->success("Class [{$this->getClassName()}] for group [{$this->getGroup()}]");
        }

        return self::SUCCESS;
    }

    /**
     * Generate Settings Class
     */
    protected function generateClass(): bool
    {
        $class = $this->getClassPath();
        $class_stub = $this->resolveStubPath(
            $this->getStub('class')
        );

        $content = $this->replaceIn(replacers: [
            '{{class}}' => Str::of(pathinfo($class, PATHINFO_FILENAME))->pascal()->toString(),
            '{{group}}' => $this->getGroup(),
            '{{namespace}}' => $this->getNamespace('Settings'),
        ], content: $this->fs->get($class_stub));

        return (bool) $this->fs->put(
            $class,
            $content,
        );
    }

    /**
     * Generate the migration file
     */
    protected function generateMigration(): bool
    {
        $migration = $this->getMigrationPath();
        $migration_stub = $this->resolveStubPath(
            $this->getStub('migration')
        );

        $content = $this->replaceIn(replacers: [
            '{{group}}' => $this->getGroup(),
        ], content: $this->fs->get($migration_stub));

        return (bool) $this->fs->put(
            $migration,
            $content,
        );
    }

    /**
     * Get the group for the settings
     */
    public function getGroup(): string
    {
        $group = Str::of($this->option('group') ?? 'default')->snake()->lower()->toString();

        if ($group !== 'default') {
            $this->ensureIsNotReserved($group);
        }

        return $group;
    }

    /**
     * Get the class path where the class settings will live
     *
     * @return string
     */
    protected function getClassPath(): string
    {
        $className = $this->getClassName().'.php';

        return app_path(
            'Settings'.
            DIRECTORY_SEPARATOR.
            $className,
        );
    }

    /**
     * The the migration path where the migration setting will live
     */
    protected function getMigrationPath(): string
    {
        $group = $this->getGroup();
        $migration_filename = implode('_', [now()->format('Y_m_d_his'), 'create', $group, 'settings']).'.php';

        // Ensure the group migration is not already created
        if ($this->fs->glob(database_path("migrations/*_create_{$group}_settings.php"))) {
            throw new \Exception("The migration [$group] settings already exists");
        }

        return database_path(
            'migrations'.
            DIRECTORY_SEPARATOR.
            $migration_filename,
        );
    }

    /**
     * Get the calculated settings class name
     */
    protected function getClassName(): string
    {
        $className = Str::snake($this->option('class-name') ?: $this->getGroup());

        if ($className === 'default') {
            $className = 'default-settings';
        }

        $className = Str::studly($className);

        $this->ensureIsNotReserved($className);

        return $className;
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(string $type): string
    {
        $stub = 'default';

        if ($this->getGroup() !== 'default') {
            $stub = 'group';
        }

        return "$type-$stub.stub";
    }

    /**
     * Ensure settings directory exists
     */
    protected function ensureSettingsDirectoryExists(): void
    {
        $this->fs->ensureDirectoryExists(app_path('Settings'));
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
     * Check if the given name is a reserved name
     *
     * @throws RuntimeException if the provided name is reserved
     */
    protected function ensureIsNotReserved(string $name): void
    {
        if (in_array($name, $this->reservedNames)) {
            throw new RuntimeException("The provided name [$name] is reserved");
        }
    }

    /**
     * Resolve the fully-qualified path to the stub.
     */
    protected function resolveStubPath($stub)
    {
        return package_path('stubs', $stub);
    }

    /**
     * Get the Laravel app namespace
     */
    protected function getNamespace(string $namespace): string
    {
        $rootNamespace = $this->getLaravel()->getNamespace();

        return trim($rootNamespace, '\\')."\\$namespace";
    }

    /**
     * Search and replace in the content
     */
    protected function replaceIn(array $replacers, string $content): string
    {
        return Str::replace(
            array_keys($replacers),
            array_values($replacers),
            $content,
        );
    }
}
