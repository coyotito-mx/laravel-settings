<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use Coyotito\LaravelSettings\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use function Coyotito\LaravelSettings\Helpers\psr4_namespace_to_path;
use function Illuminate\Filesystem\join_paths;

uses(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeInDirectory', function (string $directory) {
    expect($directory)->toBeDirectory();

    $ext = pathinfo($this->value, PATHINFO_EXTENSION);

    $files = glob(join_paths($directory, "*.$ext"));
    $files = array_map(fn (string $file): string => pathinfo($file, PATHINFO_BASENAME), $files ?: []);

    if (blank($files)) {
        test()->fail("The file [$this->value] is not in the directory [$directory]");
    }

    $filesInDirectory = implode(", ", $files);

    return expect(in_array($this->value, $files))
        ->toBeTrue("The file [$this->value] is not one of the following files [$filesInDirectory] in [$directory]");
});

expect()->extend('toBeClassSettings', function () {
    $class = class_basename($this->value);

    $directory = psr4_namespace_to_path(Str::before($this->value, "\\{$class}"));

    expect($directory)
        ->not->toBeEmpty('The provided class namespace is invalid.')
        ->and("$class.php")->toBeInDirectory($directory)
        ->and(new ReflectionClass($this->value))
        ->isSubclassOf(Settings::class)
        ->toBeTrue("The class [$class] is not a subclass of [Settings]");
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/
/**
 * Delete folder recursively
 *
 * @param string $directory The root directory to delete
 * @param bool $delete_root Should delete the root directory
 */
function rmdir_recursive(string $directory, bool $delete_root = true): bool
{
    if (! file_exists($directory)) {
        return false;
    }

    if (! is_dir($directory)) {
        throw new RuntimeException('The provided path is not a directory');
    }

    $walk = static function (string $root, bool $delete_root) use (&$walk): bool {
        $files = scandir($root);

        if ($files === false) {
            throw new RuntimeException("Unable to read directory: {$root}");
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filepath = join_paths($root, $file);

            if (is_dir($filepath)) {
                $walk($filepath, true);

                continue;
            }

            if (! unlink($filepath)) {
                throw new RuntimeException("Unable to delete file: {$filepath}");
            }
        }

        return $delete_root ? rmdir($root) : true;
    };

    return $walk($directory, $delete_root);
}
