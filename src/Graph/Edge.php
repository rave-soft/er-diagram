<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram\Graph;

class Edge
{
    public readonly string $id;

    public function __construct(
        public readonly string $source,
        public readonly string $target,
        public readonly Graph $graph,
    ) {
        $this->id = uniqid('ED-', false);
    }
}
