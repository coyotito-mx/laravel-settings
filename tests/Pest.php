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

use Illuminate\Foundation\Testing\RefreshDatabase;

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
 * @return bool
 */
function rmdir_recursive(string $directory, bool $delete_root = true): bool
{
    $walk = static function (string $root) use (&$walk, $delete_root): bool
    {
        foreach (scandir($root) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filepath = implode(DIRECTORY_SEPARATOR, [$root, $file]);

            if (is_dir($filepath)) {
                $walk($filepath, false);
                rmdir($filepath);

                continue;
            }

            unlink($filepath);
        }

        if ($delete_root) {
            return rmdir($root);
        }

        return true;
    };

    if (! file_exists($directory)) {
        return false;
    }

    if (! is_dir($directory)) {
        throw new \RuntimeException('The provided path is not a directory');
    }

    return $walk($directory, true);
}
