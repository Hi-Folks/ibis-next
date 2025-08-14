<?php

namespace Ibis\Concerns;

use function Laravel\Prompts\info;

trait PathManager
{
    protected function ensureExportDirectoryExists(): void
    {
        info('Preparing export directory ...');
        $exportDir = $this->config->getExportPath();

        if (!$this->disk->isDirectory($exportDir)) {
            $this->disk->makeDirectory($exportDir, 0755, true);
        }
    }

    protected function isAbsolutePath(string $path): bool
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

        if ((string) $path === '' || '.' === $path[0]) {
            return false;
        }

        // Windows allows absolute paths like this.
        if (preg_match('#^[a-zA-Z]:\\\\#', (string) $path)) {
            return true;
        }

        // A path starting with / or \ is absolute; anything else is relative.
        return ('/' === $path[0] || '\\' === $path[0]);
    }
}
