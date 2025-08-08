<?php

namespace Ibis\Exceptions;

use Exception;

class InvalidConfigFileException extends Exception
{
    public static function fileDoesNotExist(string $filePath): self
    {
        return new static("The configuration file '{$filePath}' does not exist.");
    }

    public static function invalidConfigFile(string $filePath): self
    {
        return new static("The configuration file '{$filePath}' is not a valid JSON file.");
    }
}
