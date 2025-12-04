<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Console\Commands;

use Coyotito\LaravelSettings\Console\Commands\GeneratorCommand as Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

use function Coyotito\LaravelSettings\Helpers\psr4_namespace_to_path;

class MakeSettingsClassCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:settings-class';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new class for settings';

    /**
     * The type of file being generated.
     */
    protected static string $type = 'class';

    /**
     * Handle the command
     */
    public function handle(): int
    {
        $className = $this->getClassName();
        $destination = $this->resolveNamespacePath(
            $this->getNamespace()
        );

        if (! $this->generateFile("$className.php", $destination)) {
            $this->components->error("Failed to create settings class [$className].");

            return self::FAILURE;
        }

        $this->components->success("Settings class [$className] created successfully.");

        return self::SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->addOption(
            'namespace',
            null,
            InputOption::VALUE_REQUIRED,
            'The namespace where the settings class will be created',
            $this->getDefaultNamespace(),
        );

        $this
            ->addReservedName('Default')
            ->addReservedName(\Coyotito\LaravelSettings\Settings::DEFAULT_GROUP)
            ->addReservedName('Settings')
            ->addReservedName('settings')
            ->addReservedName('Setting')
            ->addReservedName('setting');
    }

    /**
     * The replacements to make to the stub.
     *
     * @return array<string, string>
     */
    protected function getReplacements(): array
    {
        return [
            ...parent::getReplacements(),
            '{{class}}' => $this->getClassName(),
            '{{namespace}}' => $this->getNamespace(),
        ];
    }

    /**
     * Get the settings class name.
     */
    protected function getClassName(): string
    {
        return $this->getNameArgument();
    }

    /**
     * Get the settings namespace.
     */
    protected function getNamespace(): string
    {
        /** @var string $rootNamespace */
        $rootNamespace = $this->option('namespace') ?? $this->getDefaultNamespace();

        $this->ensureNotReserved($rootNamespace, 'namespace');

        return $rootNamespace;
    }

    /**
     * Get the default namespace for the settings class.
     */
    protected function getDefaultNamespace(): string
    {
        return "App\\Settings";
    }

    /**
     * Resolve the namespace path.
     */
    protected function resolveNamespacePath(string $namespace): string
    {
        if (blank($path = psr4_namespace_to_path($namespace))) {
            throw new \InvalidArgumentException("The namespace [$namespace] does not exist.");
        }

        File::ensureDirectoryExists($path);

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    protected function formatName(string $name): string
    {
        return Str::of($name)->snake()->studly()->toString();
    }
}
