<?php

namespace Ibis\Config;

class Sample
{
    private string $text = '';

    /**
     * @var array<string>
     */
    private array $files;

    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function addFile(string $filename): self
    {
        $this->files[] = $filename;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function files(): array
    {
        return $this->files;
    }
}
