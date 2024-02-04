<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram\Schema;

/**
 * @skiptests
 */
class EntityList
{
    /**
     * @var array<string, Entity>
     */
    private array $entities = [];

    public function addEntity(Entity $entity): void
    {
        $this->entities[$entity->className] = $entity;
    }

    /**
     * @return array<string, Entity>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * @param string[] $tables
     */
    public function filterByTables(array $tables, int $maxLevel, bool $bidirectional): void
    {
        $entities = $this->entities;

        $selectedEntities = array_filter(
            $entities,
            fn (Entity $entity) => \in_array($entity->tableName, $tables)
        );

        $this->entities = [];

        /** @var Entity $entity */
        foreach ($selectedEntities as $entity) {
            $this->addRelatedEntity($entity, $maxLevel, 1, $bidirectional, $entities);
        }
    }

    private function findRelatedEntities(
        Entity $entity,
        int $maxLevel,
        int $level,
        bool $bidirectional,
        array &$entities
    ): void {
        if ($level > $maxLevel) {
            return;
        }
        foreach ($entity->relations as $relation) {
            if ($relation->target !== null) {
                $this->addRelatedEntity($relation->target, $maxLevel, $level + 1, $bidirectional, $entities);
            }
        }
        if ($bidirectional) {
            /** @var Entity $entity1 */
            foreach ($entities as $entity1) {
                foreach ($entity1->relations as $relation) {
                    if ($relation->target === $entity) {
                        $this->addRelatedEntity($entity1, $maxLevel, $level + 1, true, $entities);
                    }
                }
            }
        }
    }

    private function addRelatedEntity(
        Entity $entity,
        int $maxLevel,
        int $level,
        bool $bidirectional,
        array &$entities
    ): void {
        $entity->filterLevel = FilterLevelEnum::tryBetter($entity->filterLevel, $level);
        $className = $entity->className;
        if (!isset($this->entities[$className])) {
            $this->entities[$className] = $entity;
            $this->findRelatedEntities($entity, $maxLevel, $level, $bidirectional, $entities);
        }
    }
}
