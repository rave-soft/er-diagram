<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram;

use RaveSoft\ErDiagram\Schema\Entity;
use RaveSoft\ErDiagram\Schema\EntityList;
use RaveSoft\ErDiagram\Schema\FilterLevelEnum;
use RaveSoft\ErDiagram\Schema\PrimaryKey;
use RaveSoft\ErDiagram\Schema\Relation;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;

/**
 * @skiptests
 */
class DoctrineSchema
{
    public function getEntityList(ObjectManager $objectManager): EntityList
    {
        $entityList = new EntityList();
        /** @var ClassMetadata $metadata */
        foreach ($objectManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            $relations = [];
            foreach ($metadata->getAssociationNames() as $associationName) {
                if ($metadata->isSingleValuedAssociation($associationName)) {
                    $relations[$associationName] = $metadata->getAssociationTargetClass($associationName);
                }
            }
            $entity = new Entity(
                $metadata->getReflectionClass()->getShortName(),
                $metadata->getReflectionClass()->getName(),
                $metadata->getTableName(),
                array_map(static fn ($item) => new PrimaryKey($item), $metadata->getIdentifier()),
                array_map(static fn ($item) => new Relation($item), $relations),
                FilterLevelEnum::Regular,
            );
            $entityList->addEntity($entity);
        }
        $entities = $entityList->getEntities();
        foreach ($entities as $entity) {
            foreach ($entity->relations as $relation) {
                $relation->target = $entities[$relation->className] ?? null;
            }
        }

        return $entityList;
    }
}
