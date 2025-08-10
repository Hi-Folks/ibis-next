<?php

namespace Ibis\Config;

class Header
{
    private string $style = 'font-style: italic; text-align: right; border-bottom: solid 1px #808080';

    private string $text = '';

    public function style(string $style): self
    {
        $this->style = $style;

        return $this;
    }

    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
