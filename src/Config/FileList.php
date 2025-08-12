<?php

namespace Ibis\Config;

class FileList
{
    /**
     * @var array<string>
     */
    private array $files = [];

    public function addFile(string $file): self
    {
        $this->files[] = $file;

        return $this;
    }

    public function files(): array
    {
        return $this->files;
    }
}
