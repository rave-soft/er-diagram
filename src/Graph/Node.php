<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram\Graph;

class Node
{
    public int $x;
    public int $y;

    public function __construct(
        public readonly string $id,
        public readonly Graph $graph,
    ) {
        $this->x = 0;
        $this->y = 0;
    }
}
