<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram\Schema;

class Relation
{
    public ?Entity $target = null;
    public readonly string $uniqId;

    public function __construct(
        public string $className,
    ) {
        $this->uniqId = uniqid('FK-', false);
    }
}
