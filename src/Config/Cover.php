<?php

namespace Ibis\Config;

class Cover
{
    private string $src = 'cover-ibis.webp';

    private string $position = 'absolute';

    private float $height = 297;

    private float $width = 210;

    private float $left = 0;

    private float $right = 0;

    private float $top = -0.2;

    private float $bottom = 0;

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

    public function positionStyle(): string
    {
        return "position: {$this->position}; left: {$this->left}; right: {$this->right}; top: {$this->top}; bottom: {$this->bottom};";
    }

    public function dimensionsStyle(): string
    {
        return "width: {$this->width}mm; height: {$this->height}mm; margin: 0";
    }
}
