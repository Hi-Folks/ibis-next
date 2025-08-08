<?php

namespace Ibis\Config;

readonly class Font
{
    public function __construct(
        public string $name,
        public string $src,
    ) {}
}
