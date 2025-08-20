<?php

namespace Ibis\Concerns;

use Illuminate\Support\Str;

trait PathManager
{
    public static function basePath(): string
    {
        return file_exists(__DIR__ . '/../../autoload.php') ? '../../' : './';
    }

    public static function buildPath(array $pathFragments): string
    {
        $path = Str::deduplicate(implode('/', $pathFragments), '/');
        // Replace multiple "./" at the beginning with a single "./"
        return preg_replace('/^(?:\.\\/)+/', './', $path);
    }

    public static function isAbsolutePath(string $path): bool
    {
        /*
         * Check to see if the path is a stream and check to see if its an actual
         * path or file as realpath() does not support stream wrappers.
         */
        if ((is_dir($path) || is_file($path))) {
            return true;
        }

        /*
         * This is definitive if true but fails if $path does not exist or contains
         * a symbolic link.
         */
        if (realpath($path) === $path) {
            return true;
        }

        if ($path === '' || '.' === $path[0]) {
            return false;
        }

        // Windows allows absolute paths like this.
        if (preg_match('#^[a-zA-Z]:\\\\#', $path)) {
            return true;
        }

        // A path starting with / or \ is absolute; anything else is relative.
        return ('/' === $path[0] || '\\' === $path[0]);
    }
}
