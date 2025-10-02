<?php

namespace Ibis\Exceptions;

use Exception;

class InvalidConfigFileException extends Exception
{
    public static function fileDoesNotExist(string $filePath): self
    {
        return new self(sprintf("The configuration file '%s' does not exist.", $filePath));
    }

    public static function invalidConfigFile(string $filePath): self
    {
        return new self(sprintf("The configuration file '%s' is not a valid JSON file.", $filePath));
    }

    public static function oldConfigFile(string $filePath): self
    {
        return new self(sprintf("Old Config file detected in  '%s'.", $filePath));
    }
}
