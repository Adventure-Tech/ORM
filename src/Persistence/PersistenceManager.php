<?php

namespace AdventureTech\ORM\Persistence;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\AttachingInconsistentEntitiesException;
use AdventureTech\ORM\Exceptions\AttachingToInvalidRelationException;
use AdventureTech\ORM\Exceptions\BadlyConfiguredPersistenceManagerException;
use AdventureTech\ORM\Exceptions\IdSetForInsertException;
use AdventureTech\ORM\Exceptions\InvalidEntityTypeException;
use AdventureTech\ORM\Exceptions\MissingIdException;
use AdventureTech\ORM\Exceptions\MissingOwningRelationException;
use AdventureTech\ORM\Exceptions\MissingValueForColumnException;
use AdventureTech\ORM\Mapping\Linkers\OwningLinker;
use AdventureTech\ORM\Mapping\Linkers\PivotLinker;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @template T of object
 */
class PersistenceManager
{
    /**
     * @var class-string<T>
     */
    protected static string $entity;

    private function __construct()
    {
    }

    /**
     * @param  T  $entity
     *
     * @return T
     */
    public static function insert(object $entity): object
    {
        $entityReflection = self::getEntityReflection($entity);
        $arr = [];
        EntityReflection::new(static::$entity);

        $id = $entityReflection->getId();

        foreach ($entityReflection->getManagedColumns() as $property => $managedColumn) {
            $entity->{$property} = $managedColumn->getInsertValue();
        }

        foreach ($entityReflection->getSoftDeletes() as $property => $softDelete) {
            $entity->{$property} = null;
        }

        foreach ($entityReflection->getMappers() as $property => $mapper) {
            if ($entityReflection->checkPropertyInitialized($property, $entity)) {
                if ($property === $id) {
                    throw new IdSetForInsertException();
                }
                $arr = array_merge($arr, $mapper->serialize($entity->{$property}));
            } elseif ($property !== $id) {
                throw new MissingValueForColumnException($property);
            }
        }

        $arr = array_merge($arr, self::resolveOwningRelations($entity, $entityReflection));

        $id = DB::table($entityReflection->getTableName())->insertGetId($arr);
        $entity->{$entityReflection->getId()} = $id;
        return $entity;
    }

    /**
     * @param  T  $entity
     *
     * @return int
     */
    public static function update(object $entity): int
    {
        $entityReflection = self::getEntityReflection($entity);
        $arr = [];

        if (!isset($entity->{$entityReflection->getId()})) {
            throw new MissingIdException('Must set ID column when updating');
        }

        foreach ($entityReflection->getManagedColumns() as $property => $managedColumn) {
            $entity->{$property} = $managedColumn->getUpdateValue($entity->{$property} ?? null);
        }

        foreach ($entityReflection->getSoftDeletes() as $property => $softDelete) {
            $entity->{$property} = null;
        }

        foreach ($entityReflection->getMappers() as $property => $mapper) {
            if ($entityReflection->checkPropertyInitialized($property, $entity)) {
                $arr = array_merge($arr, $mapper->serialize($entity->{$property}));
            }
        }

        $arr = array_merge($arr, self::resolveOwningRelations($entity, $entityReflection));

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
    public static function delete(object $entity): int
    {
        $entityReflection = self::getEntityReflection($entity);

        $query = DB::table($entityReflection->getTableName())
            ->where($entityReflection->getId(), '=', $entity->{$entityReflection->getId()});

        foreach ($entityReflection->getSoftDeletes() as $property => $softDelete) {
            /** @var Mapper<mixed> $mapper */
            $mapper = $entityReflection->getMappers()->get($property);
            $datetime = $softDelete->getDatetime();
            return $query->update($mapper->serialize($datetime));
        }

        return $query->delete();
    }

    /**
     * @param  T  $entity
     *
     * @return int
     */
    public static function forceDelete(object $entity): int
    {
        $entityReflection = self::getEntityReflection($entity);
        return DB::table($entityReflection->getTableName())
            ->where($entityReflection->getId(), '=', $entity->{$entityReflection->getId()})
            ->delete();
    }

    public static function attach(object $entity, Collection $linkedEntities, string $relation): int
    {
        $entityReflection = self::getEntityReflection($entity);
        $linker = $entityReflection->getLinkers()->get($relation);
        if (!($linker instanceof PivotLinker)) {
            throw new AttachingToInvalidRelationException();
        }
        if (!$entityReflection->checkPropertyInitialized($entityReflection->getId(), $entity)) {
            throw new MissingIdException('Must set ID column on base entity when attaching');
        }
        $linkedEntityReflection = EntityReflection::new($linker->getTargetEntity());
        $data = $linkedEntities->map(function ($linkedEntity) use ($entityReflection, $entity, $linker, $linkedEntityReflection) {
            if ($linkedEntity::class !== $linkedEntityReflection->getClass()) {
                throw new AttachingInconsistentEntitiesException();
            }
            if (!$linkedEntityReflection->checkPropertyInitialized($linkedEntityReflection->getId(), $linkedEntity)) {
                throw new MissingIdException('Must set ID columns of all entities when attaching');
            }
            // TODO: ensure that ids are set!
            return [
                $linker->getOriginForeignKey() => $entity->{$entityReflection->getId()},
                $linker->getTargetForeignKey() => $linkedEntity->{$linkedEntityReflection->getId()},
            ];
        })->toArray();

        $entity->{$relation} = $linkedEntities;

        return DB::table($linker->getPivotTable())->upsert(
            $data,
            [$linker->getOriginForeignKey(), $linker->getTargetForeignKey()]
        );
    }

    /**
     * @param  object  $entity
     * @param  EntityReflection<T>  $entityReflection
     * @return array<string,mixed>
     */
    private static function resolveOwningRelations(object $entity, EntityReflection $entityReflection): array
    {
        $arr = [];
        foreach ($entityReflection->getLinkers() as $property => $linker) {
            if ($linker instanceof OwningLinker) {
                if (!$entityReflection->checkPropertyInitialized($property, $entity)) {
                    throw new MissingOwningRelationException('Must set all non-nullable owning relations');
                }
                $linkedEntityReflection = EntityReflection::new($linker->getTargetEntity());
                $linkedEntity = $entity->{$property};
                $linkedEntityId = $linkedEntityReflection->getId();
                $initialized = $linkedEntityReflection->checkPropertyInitialized($linkedEntityId, $linkedEntity);
                if (!$initialized && !is_null($linkedEntity)) {
                    throw new MissingOwningRelationException('Owned linked entity must have valid ID set');
                }
                if (!is_null($linkedEntity)) {
                    $arr[$linker->getForeignKey()] = $entity->{$property}->{$linkedEntityId};
                }
            }
        }
        return $arr;
    }

    /**
     * @param  T  $entity
     *
     * @return EntityReflection<T>
     */
    private static function getEntityReflection(object $entity): EntityReflection
    {
        if (!isset(static::$entity)) {
            throw new BadlyConfiguredPersistenceManagerException();
        }
        if (get_class($entity) !== static::$entity) {
            throw new InvalidEntityTypeException('Invalid entity type used in persistence manager');
        }
        return EntityReflection::new(static::$entity);
    }
}
