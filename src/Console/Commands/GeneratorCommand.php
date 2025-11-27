<?php

declare(strict_types=1);

namespace Coyotito\LaravelSettings\Console\Commands;

use Coyotito\LaravelSettings\Console\Commands\Concerns\InteractsWithStubs;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Illuminate\Filesystem\join_paths;
use function Laravel\Prompts\text;

abstract class GeneratorCommand extends Command implements PromptsForMissingInput
{
    use InteractsWithStubs;

    /**
     * The type of file being generated.
     *
     * @var string
     */
    protected static string $type;

    /**
     * Comprehensive list of reserved words
     *
     * @var list<string>
     */
    protected $reservedNames = [
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $type = static::$type;

        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            "The name of the settings $type",
        );

        $this->addOption(
            'group',
            'g',
            InputOption::VALUE_REQUIRED,
            "The group to use with settings $type",
            'default'
        );
    }


    /**
     * Check if the given name is a reserved name
     *
     * @throws RuntimeException if the provided name is reserved
     */
    protected function ensureNotReserved(string $name): void
    {
        if (in_array($name, $this->reservedNames)) {
            throw new \RuntimeException("The provided name [$name] is reserved.");
        }
    }

    /**
     * Add a new reserved name
     */
    public function addReservedName(string $name): static
    {
        $this->reservedNames[] = $name;

        return $this;
    }

    /**
     * Generate file
     *
     * @param string $name The name of the file
     * @param string $to The path to store the file
     * @return bool
     */
    public function generateFile(string $name, string $to): bool
    {
        $stub = $this->getStubPath(
            $this->resolveStub(static::$type)
        );

        $content = Str::replace(
            array_keys($this->getReplacements()),
            array_values($this->getReplacements()),
            File::get($stub)
        );

        if (File::exists(join_paths($to, $name))) {
            return false;
        }

        return (bool) File::put(join_paths($to, $name), $content);
    }

    /**
     * Get the group
     *
     * @return string
     */
    protected function getGroup(): string
    {
        $group = $this->option('group');

        if ($group !== 'default') {
            $this->ensureNotReserved($group);
        }

        return Str::of($group)->snake()->slug()->toString();
    }

    /**
     * {@inheritdoc}
     */
    protected function promptForMissingArgumentsUsing()
    {
        $type = static::$type;

        return [
            'name' => function () use ($type): string {
                $value = text(label: "Enter the name of the settings $type", required: true);

                $this->ensureNotReserved($value);

                return $this->formatName($value);
            },
        ];
    }

    /**
     * Format the name argument
     *
     * @return string
     */
    abstract protected function formatName(string $name): string;

    /**
     * Get the replacements and placeholders
     */
    abstract protected function getReplacements(): array;
}
