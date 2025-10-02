<?php

namespace Ibis\Config;

class Sample
{
    private string $text = '';

    /**
     * @var array<string>
     */
    private array $files = [];

    /**
     * @var array<array>
     */
    private array $pages = [];


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

    public function pages(): array
    {
        return $this->pages;
    }

    public function addPages(array $pages): self
    {
        $this->pages[] = $pages;

        return $this;
    }
}
