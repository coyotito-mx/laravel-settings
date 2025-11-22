<?php

namespace Coyotito\LaravelSettings\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Str;

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
                            { --c|class-name : The name of the settings class }
                            { --without-class : Without the class settings }';

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
        return parent::__construct();
    }

    public function handle(): int
    {
        // Ensure directory exists
        if (! $this->fs->isDirectory(app_path('Settings'))) {
            $this->fs->makeDirectory(app_path('Settings'));
        }

        $this->generateMigration();

        if (! $this->option('without-class')) {
            $this->generateClass();
        }

        $this->components->success("Migration for [{$this->getGroup()}] group created");

        return self::SUCCESS;
    }

    protected function generateMigration(): bool
    {
        $migration = $this->createMigration();
        $migration_stub = $this->resolveStubPath($this->getStub());

        $content = $this->replaceIn(replacers: [
            '{{group}}' => $this->getGroup(),
        ], content: $this->fs->get($migration_stub));

        return (bool) $this->fs->put(
            $migration,
            $content,
        );
    }

    protected function generateClass(): bool
    {
        $class = $this->createClass();
        $class_stub = $this->resolveStubPath('class.stub');

        $content = $this->replaceIn(replacers: [
            '{{class}}' => Str::of(pathinfo($class, PATHINFO_FILENAME))->pascal()->toString(),
            '{{namespace}}' => $this->getNamespace('Settings'),
        ], content: $this->fs->get($class_stub));

        return (bool) $this->fs->put(
            $class,
            $content,
        );
    }

    protected function replaceIn(array $replacers, string $content): string
    {
        return Str::replace(
            array_keys($replacers),
            array_values($replacers),
            $content,
        );
    }

    public function getGroup(): string
    {
        $group = Str::of($this->option('group') ?? 'default')->snake()->lower()->toString();

        if ($group !== 'default') {
            $this->ensureIsNotReserved($group);
        }

        return $group;
    }

    protected function createClass(): string
    {
        $className = $this->getClassName();

        return base_path(
            'app'.
            DIRECTORY_SEPARATOR.
            'Settings'.
            DIRECTORY_SEPARATOR.
            $className,
        );
    }

    protected function getClassName(): string
    {
        $className = Str::of($this->option('class-name') ?? '')->lower()->snake()->toString() ?: $this->getGroup();

        if ($className === 'default') {
            $className = 'default-settings';
        }

        $className = Str::of($className)->slug()->pascal()->append('.php')->toString();

        $this->ensureIsNotReserved($className);

        return $className;
    }

    protected function createMigration(): string
    {
        $group = $this->option('group') ?? 'default';
        $migration_filename = implode('_', [now()->format('Y_m_d_his'), 'create', $group, 'settings']).'.php';

        // Ensure the group migration is not already created
        if ($this->fs->glob(database_path("migrations/*_create_{$group}_settings.php"))) {
            throw new \Exception("The migration [$group] settings already exists");
        }

        return base_path(
            'database'.
            DIRECTORY_SEPARATOR.
            'migrations'.
            DIRECTORY_SEPARATOR.
            $migration_filename,
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $stub = 'default';

        if ($this->option('group') !== 'default') {
            $stub = 'group';
        }

        return "migration-$stub.stub";
    }

    protected function ensureIsNotReserved(string $name): void
    {
        if (in_array($name, $this->reservedNames)) {
            throw new RuntimeException("The provided name [$name] is reserved");
        }
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return package_path('stubs', $stub);
    }

    protected function getNamespace(string $namespace): string
    {
        $rootNamespace = $this->laravel->getNamespace();

        return trim($rootNamespace, '\\')."\\$namespace";
    }
}
