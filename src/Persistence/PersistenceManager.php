<?php

namespace AdventureTech\ORM\Persistence;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\IdSetForInsertException;
use AdventureTech\ORM\Exceptions\InvalidEntityTypeException;
use AdventureTech\ORM\Exceptions\MissingBelongsToRelationException;
use AdventureTech\ORM\Exceptions\MissingIdForUpdateException;
use AdventureTech\ORM\Exceptions\MissingValueForColumnException;
use AdventureTech\ORM\Mapping\Linkers\OwningLinker;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use Illuminate\Support\Facades\DB;
use LogicException;

use function array_merge;
use function get_class;

/**
 * @template T of object
 */
abstract class PersistenceManager
{
    /**
     * @var class-string<T>
     */
    protected static string $entity;

    /**
     * @var EntityReflection<T>
     */
    protected EntityReflection $entityReflection;

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
            if ($mapper->isInitialized($entity)) {
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
            throw new MissingIdForUpdateException();
        }

        foreach ($entityReflection->getManagedColumns() as $property => $managedColumn) {
            $entity->{$property} = $managedColumn->getUpdateValue($entity->{$property} ?? null);
        }

        foreach ($entityReflection->getSoftDeletes() as $property => $softDelete) {
            $entity->{$property} = null;
        }

        foreach ($entityReflection->getMappers() as $property => $mapper) {
            if ($mapper->isInitialized($entity)) {
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

//    public function attach()
//    {
//    }

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
                // TODO: replace with proper isset check to allow for nullable relations
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

    /**
     * @param  T  $entity
     *
     * @return EntityReflection<T>
     */
    private static function getEntityReflection(object $entity): EntityReflection
    {
        if (!isset(static::$entity)) {
            throw new LogicException('Must set static $entity property');
        }
        if (get_class($entity) !== static::$entity) {
            throw new InvalidEntityTypeException('Invalid entity type used in persistence manager');
        }
        return EntityReflection::new(static::$entity);
    }
}
