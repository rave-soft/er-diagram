<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram\Schema;

class PrimaryKey
{
    public readonly string $uniqId;

    public function __construct(
        public string $name,
    ) {
        $this->uniqId = uniqid('PK-', false);
    }
}
