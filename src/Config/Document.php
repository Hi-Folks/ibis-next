<?php

namespace Ibis\Config;

class Document
{
    private float $height;

    private float $width;

    private float $marginLeft;

    private float $marginRight;

    private float $marginTop;

    private float $marginBottom;

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

    public function marginLeft(float $marginLeft): self
    {
        $this->marginLeft = $marginLeft;

        return $this;
    }

    public function marginRight(float $marginRight): self
    {
        $this->marginRight = $marginRight;

        return $this;
    }

    public function marginTop(float $marginTop): self
    {
        $this->marginTop = $marginTop;

        return $this;
    }

    public function marginBottom(float $marginBottom): self
    {
        $this->marginBottom = $marginBottom;

        return $this;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function getMarginLeft(): float
    {
        return $this->marginLeft;
    }

    public function getMarginRight(): float
    {
        return $this->marginRight;
    }

    public function getMarginTop(): float
    {
        return $this->marginTop;
    }

    public function getMarginBottom(): float
    {
        return $this->marginBottom;
    }
}
