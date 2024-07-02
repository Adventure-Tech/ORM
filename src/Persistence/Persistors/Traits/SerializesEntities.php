<?php

namespace AdventureTech\ORM\Persistence\Persistors\Traits;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\Exceptions\PersistenceException;
use AdventureTech\ORM\Mapping\Linkers\OwningLinker;

/**
 * @template T of object
 */
trait SerializesEntities
{
    /**
     * @use ReflectsEntities<T>
     */
    use ReflectsEntities;

    /**
     * @param  T  $entity
     * @param  bool  $withIdColumn
     * @return array<string,mixed>
     */
    protected function serializeEntity(object $entity, bool $withIdColumn): array
    {
        $data = [];
        foreach ($this->entityReflection->getMappers() as $property => $mapper) {
            if (
                !$withIdColumn
                && $this->entityReflection->hasAutogeneratedId()
                && $property === $this->entityReflection->getIdProperty()
            ) {
                continue;
            }
            if (!$this->entityReflection->allowsNull($property) && !EntityAccessorService::isset($entity, $property)) {
                throw new PersistenceException('Must set non-nullable property "' . $property . '".');
            }
            $data[] = $mapper->serialize(EntityAccessorService::get($entity, $property));
        }

        $data[] = $this->resolveOwningRelations($entity);
        return array_merge(...$data);
    }

    /**
     * @param  object  $entity
     * @return array<string,mixed>
     */
    private function resolveOwningRelations(object $entity): array
    {
        $data = [];
        foreach ($this->entityReflection->getOwningLinkers() as $property => $linker) {
            if (!$this->entityReflection->allowsNull($property) && !EntityAccessorService::isset($entity, $property)) {
                throw new PersistenceException('Must set all non-nullable owning relations.');
            }
            if (EntityAccessorService::isset($entity, $property)) {
                /** @var object $linkedEntity */
                $linkedEntity = EntityAccessorService::get($entity, $property);
                if (!EntityAccessorService::issetId($linkedEntity)) {
                    throw new PersistenceException('Owned linked entity must have valid ID set.');
                }
                $data[$linker->getForeignKey()] = EntityAccessorService::getId($linkedEntity);
            } else {
                $data[$linker->getForeignKey()] = null;
            }
        }
        return $data;
    }
}