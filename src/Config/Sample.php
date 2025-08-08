<?php

namespace Ibis\Config;

class Sample
{
    private string $text;

    /**
     * @var array<array<int>>
     */
    private array $pages;

    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function addPages(int $startPage, int $endPage): self
    {
        $this->pages[] = [$startPage, $endPage];

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function pages(): array
    {
        return $this->pages;
    }
}
