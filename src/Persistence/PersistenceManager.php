<?php

namespace AdventureTech\ORM\Persistence;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\BadlyConfiguredPersistenceManagerException;
use AdventureTech\ORM\Exceptions\IdSetForInsertException;
use AdventureTech\ORM\Exceptions\InvalidEntityTypeException;
use AdventureTech\ORM\Exceptions\MissingBelongsToRelationException;
use AdventureTech\ORM\Exceptions\MissingIdForUpdateException;
use AdventureTech\ORM\Exceptions\MissingValueForColumnException;
use AdventureTech\ORM\Mapping\Linkers\BelongsToLinker;
use Illuminate\Support\Facades\DB;

use function array_merge;
use function get_class;

/**
 * @template T of object
 */
abstract class PersistenceManager
{
    protected string $entity;
    //protected array $rules = [];

    public function __construct()
    {
        if (!isset($this->entity)) {
            throw new BadlyConfiguredPersistenceManagerException();
        }
    }

    /**
     * @param  T  $entity
     *
     * @return T
     */
    public function insert(object $entity): object
    {
        $this->checkType($entity);
        $entityReflection = EntityReflection::new(get_class($entity));
        $arr = [];

        $id = $entityReflection->getId();

        foreach ($entityReflection->getManagedDatetimes() as $property => $managedDatetime) {
            $entity->{$property} = $managedDatetime->getInsertDatetime();
        }

        foreach ($entityReflection->getSoftDeletes() as $property => $softDelete) {
            $entity->{$property} = null;
        }

        foreach ($entityReflection->getMappers() as $property => $mapper) {
            if ($mapper->isInitialized($entity)) {
                if ($property === $id) {
                    throw new IdSetForInsertException();
                }
                $arr = array_merge($arr, $mapper->serialize($entity->{$property}));
            } elseif ($property !== $id) {
                throw new MissingValueForColumnException($property);
            }
        }

        $arr = array_merge($arr, $this->resolveBelongsToRelation($entityReflection, $entity));

        $id = DB::table($entityReflection->getTableName())->insertGetId($arr);
        $entity->{$entityReflection->getId()} = $id;
        return $entity;
    }

//    /**
//     * @template key of string|int
//     * @param  Collection<key,T>  $entities
//     *
//     * @return Collection<key,T>
//     */
//    public function insertMultiple(Collection $entities): Collection
//    {
//        return $entities->map(fn ($entity) => self::insert($entity));
//    }

    /**
     * @param  T  $entity
     *
     * @return int
     */
    public function update(object $entity): int
    {
        $this->checkType($entity);

        $entityReflection = EntityReflection::new(get_class($entity));
        $arr = [];

        if (!isset($entity->{$entityReflection->getId()})) {
            throw new MissingIdForUpdateException();
        }

        foreach ($entityReflection->getManagedDatetimes() as $property => $managedDatetime) {
            $entity->{$property} = $managedDatetime->getUpdateDatetime($entity->{$property} ?? null);
        }

        foreach ($entityReflection->getSoftDeletes() as $property => $softDelete) {
            // TODO: throw exception if not already null
            $entity->{$property} = null;
        }

        foreach ($entityReflection->getMappers() as $property => $mapper) {
            if ($mapper->isInitialized($entity)) {
                $arr = array_merge($arr, $mapper->serialize($entity->{$property}));
            }
        }

        $arr = array_merge($arr, $this->resolveBelongsToRelation($entityReflection, $entity));

        // TODO: filter on soft-delete columns?
        return DB::table($entityReflection->getTableName())
            ->where($entityReflection->getId(), '=', $entity->{$entityReflection->getId()})
            ->update($arr);
    }

    /**
     * @param  T  $entity
     *
     * @return int
     */
    public function delete(object $entity): int
    {
        $this->checkType($entity);

        $entityReflection = EntityReflection::new(get_class($entity));

        $query = DB::table($entityReflection->getTableName())
            ->where($entityReflection->getId(), '=', $entity->{$entityReflection->getId()});

        foreach ($entityReflection->getSoftDeletes() as $property => $softDelete) {
            $mapper = $entityReflection->getMappers()->get($property);
            $datetime = $softDelete->getDatetime();
            return $query->update($mapper->serialize($datetime));
        }

        return $query->delete();
    }

//    public function attach()
//    {
//    }

    /**
     * @param  T  $entity
     *
     * @return void
     */
    private function checkType(object $entity): void
    {
        if (get_class($entity) !== $this->entity) {
            throw new InvalidEntityTypeException('Invalid entity type used in persistence manager');
        }
    }

    private function resolveBelongsToRelation(EntityReflection $entityReflection, object $entity): array
    {
        $arr = [];
        foreach ($entityReflection->getLinkers() as $property => $linker) {
            if ($linker instanceof BelongsToLinker) {
                if (!isset($entity->{$property})) {
                    throw new MissingBelongsToRelationException('Must set all BelongsTo relations');
                }
                $linkedEntityReflection = EntityReflection::new($linker->getTargetEntity());
                if (!isset($entity->{$property}->{$linkedEntityReflection->getId()})) {
                    throw new MissingBelongsToRelationException('Linked BelongsTo entity must have valid ID set');
                }
                $arr[$linker->getForeignKey()] = $entity->{$property}->{$linkedEntityReflection->getId()};
            }
        }
        return $arr;
    }
}
