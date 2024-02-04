<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram\Schema;

class Entity
{
    public readonly string $uniqId;

    /**
     * @param PrimaryKey[] $primaryKeys
     * @param Relation[]   $relations
     */
    public function __construct(
        public readonly string $shortName,
        public readonly string $className,
        public readonly string $tableName,
        public readonly array $primaryKeys,
        public readonly array $relations,
        public FilterLevelEnum $filterLevel,
    ) {
        $this->uniqId = uniqid('EN-', false);
    }
}
