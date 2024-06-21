<?php

namespace AdventureTech\ORM\Persistence\Persistors\Traits;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\Exceptions\PersistenceException;

/**
 * @template Entity of object
 */
trait ChecksEntityType
{
    /**
     * @use ReflectsEntities<Entity>
     */
    use ReflectsEntities;

    /**
     * @param  object  $entity
     * @return void
     */
    protected function checkType(object $entity): void
    {
        if (get_class($entity) !== $this->entityReflection->getClass()) {
            throw new PersistenceException(sprintf(
                $this->entityCheckMessages[__FUNCTION__] ?? 'Cannot handle entity of type %s with persistence manager configured for entities of type %s.',
                get_class($entity),
                $this->entityReflection->getClass()
            ));
        }
    }

    protected function checkCount(int $expected, int $actual): void
    {
        if ($expected !== $actual) {
            throw new PersistenceException(sprintf(
                $this->entityCheckMessages[__FUNCTION__] ?? 'Could not handle all entities. %d handled out of %d.',
                $actual,
                $expected
            ));
        }
    }

    /**
     * @param  object  $entity
     * @return void
     */
    protected function checkIdSet(object $entity): void
    {
        if (!EntityAccessorService::issetId($entity)) {
            throw new PersistenceException($this->entityCheckMessages[__FUNCTION__] ?? 'Must set ID column when handling entities.');
        }
    }
}
