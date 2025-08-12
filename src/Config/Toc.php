<?php

namespace Ibis\Config;

class Toc
{
    private int $h1 = 0;

    private int $h2 = 0;

    private int $h3 = 1;

    public function h1(int $h1): self
    {
        $this->h1 = $h1;

        return $this;
    }

    public function h2(int $h2): self
    {
        $this->h2 = $h2;

        return $this;
    }

    public function h3(int $h3): self
    {
        $this->h3 = $h3;

        return $this;
    }

    public function getH1(): int
    {
        return $this->h1;
    }

    public function getH2(): int
    {
        return $this->h2;
    }

    public function getH3(): int
    {
        return $this->h3;
    }

    public function toArray(): array
    {
        return [
            'h1' => $this->h1,
            'h2' => $this->h2,
            'h3' => $this->h3,
        ];
    }
}
