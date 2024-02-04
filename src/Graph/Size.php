<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram\Graph;

class Size
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
    ) {
    }
}
