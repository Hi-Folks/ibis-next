<?php

namespace Ibis\Config;

class Cover
{
    private string $src;

    private string $position;

    private float $height;

    private float $width;

    private float $left;

    private float $right;

    private float $top;

    private float $bottom;

    public function src(string $src): self
    {
        $this->src = $src;

        return $this;
    }

    public function position(string $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function height(float $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function width(float $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function left(float $left): self
    {
        $this->left = $left;

        return $this;
    }

    public function right(float $right): self
    {
        $this->right = $right;

        return $this;
    }

    public function top(float $top): self
    {
        $this->top = $top;

        return $this;
    }

    public function bottom(float $bottom): self
    {
        $this->bottom = $bottom;

        return $this;
    }

    public function getSrc(): string
    {
        return $this->src;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function getLeft(): float
    {
        return $this->left;
    }

    public function getRight(): float
    {
        return $this->right;
    }

    public function getTop(): float
    {
        return $this->top;
    }

    public function getBottom(): float
    {
        return $this->bottom;
    }
}
