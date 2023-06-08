<?php

namespace AdventureTech\ORM\Persistence;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\InconsistentEntitiesException;
use AdventureTech\ORM\Exceptions\BadlyConfiguredPersistenceManagerException;
use AdventureTech\ORM\Exceptions\CannotRestoreHardDeletedRecordException;
use AdventureTech\ORM\Exceptions\IdSetForInsertException;
use AdventureTech\ORM\Exceptions\InvalidEntityTypeException;
use AdventureTech\ORM\Exceptions\InvalidRelationException;
use AdventureTech\ORM\Exceptions\MissingIdException;
use AdventureTech\ORM\Exceptions\MissingOwningRelationException;
use AdventureTech\ORM\Exceptions\MissingValueForColumnException;
use AdventureTech\ORM\Exceptions\RecordNotFoundException;
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
     * @return void
     */
    public static function insert(object $entity): void
    {
        $entityReflection = self::getEntityReflection($entity);
        $data = [];
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
                $data = array_merge($data, $mapper->serialize($entity->{$property}));
            } elseif ($property !== $id) {
                throw new MissingValueForColumnException($property);
            }
        }

        $data = array_merge($data, self::resolveOwningRelations($entity, $entityReflection));

        $id = DB::table($entityReflection->getTableName())->insertGetId($data);
        $entity->{$entityReflection->getId()} = $id;
    }

    /**
     * @param  T  $entity
     *
     * @return void
     */
    public static function update(object $entity): void
    {
        $entityReflection = self::getEntityReflection($entity);
        $data = [];

        self::checkIdIsSet($entityReflection, $entity, 'Must set ID column when updating');

        $query = DB::table($entityReflection->getTableName())
            ->where($entityReflection->getId(), '=', $entity->{$entityReflection->getId()});

        foreach ($entityReflection->getManagedColumns() as $property => $managedColumn) {
            $updateValue = $managedColumn->getUpdateValue($entity->{$property} ?? null);
            // TODO: distinction between null and not updating
            if (!is_null($updateValue)) {
                $entity->{$property} = $updateValue;
            } else {
                unset($entity->{$property});
            }
        }

        foreach ($entityReflection->getSoftDeletes() as $property => $softDelete) {
            $entity->{$property} = null;
            // TODO: this is a duplicate
            /** @var Mapper<mixed> $mapper */
            $mapper = $entityReflection->getMappers()->get($property);
            $query->whereNull($mapper->getColumnNames()[0]);
        }

        foreach ($entityReflection->getMappers() as $property => $mapper) {
            if ($entityReflection->checkPropertyInitialized($property, $entity)) {
                $data = array_merge($data, $mapper->serialize($entity->{$property}));
            }
        }

        $data = array_merge($data, self::resolveOwningRelations($entity, $entityReflection));

        $int = $query->update($data);
        self::checkNumberOfRowsAffected($int, 'Could not update entity');
    }

    /**
     * @param  T  $entity
     *
     * @return void
     */
    public static function delete(object $entity): void
    {
        $entityReflection = self::getEntityReflection($entity);

        self::checkIdIsSet($entityReflection, $entity, 'Must set ID column when deleting');

        $data = [];
        foreach ($entityReflection->getSoftDeletes() as $property => $softDelete) {
            /** @var Mapper<mixed> $mapper */
            $mapper = $entityReflection->getMappers()->get($property);
            $datetime = $softDelete->getDatetime();
            $entity->{$property} = $datetime;
            $data = array_merge($data, $mapper->serialize($datetime));
        }

        if ($data) {
            $int = DB::table($entityReflection->getTableName())
                ->where($entityReflection->getId(), '=', $entity->{$entityReflection->getId()})
                ->update($data);
        } else {
        // TODO: should this call forceDelete() instead?
            $int = DB::table($entityReflection->getTableName())
                ->where($entityReflection->getId(), '=', $entity->{$entityReflection->getId()})
                ->delete();
        }
        self::checkNumberOfRowsAffected($int, 'Could not delete entity');
    }

    /**
     * @param  T  $entity
     *
     * @return void
     */
    public static function forceDelete(object $entity): void
    {
        $entityReflection = self::getEntityReflection($entity);
        self::checkIdIsSet($entityReflection, $entity, 'Must set ID column when deleting');
        $int = DB::table($entityReflection->getTableName())
            ->where($entityReflection->getId(), '=', $entity->{$entityReflection->getId()})
            ->delete();
        self::checkNumberOfRowsAffected($int, 'Could not force-delete entity');
    }

    /**
     * @param  T  $entity
     *
     * @return void
     */
    public static function restore(object $entity): void
    {
        $entityReflection = self::getEntityReflection($entity);

        self::checkIdIsSet($entityReflection, $entity, 'Must set ID column when restoring');

        $data = [];
        foreach ($entityReflection->getSoftDeletes() as $property => $softDelete) {
            /** @var Mapper<mixed> $mapper */
            $mapper = $entityReflection->getMappers()->get($property);
            $entity->{$property} = null;
            $data = array_merge($data, $mapper->serialize(null));
        }
        if ($data) {
            $int = DB::table($entityReflection->getTableName())
                ->where($entityReflection->getId(), '=', $entity->{$entityReflection->getId()})
                ->update($data);
            self::checkNumberOfRowsAffected($int, 'Could not restore entity');
        } else {
            throw new CannotRestoreHardDeletedRecordException();
        }
    }

    /**
     * @param  T  $entity
     * @param  Collection<int|string,object>  $linkedEntities
     * @param  string  $relation
     * @return int
     */
    public static function attach(object $entity, Collection $linkedEntities, string $relation): int
    {
        $entityReflection = self::getEntityReflection($entity);
        $linker = $entityReflection->getLinkers()->get($relation);
        if (!($linker instanceof PivotLinker)) {
            throw new InvalidRelationException('Can only attach pure many-to-many relations');
        }
        self::checkIdIsSet($entityReflection, $entity, 'Must set ID column on base entity when attaching');
        $linkedEntityReflection = EntityReflection::new($linker->getTargetEntity());
        $data = $linkedEntities->map(function ($linkedEntity) use (
            $entityReflection,
            $entity,
            $linker,
            $linkedEntityReflection
        ) {
            if ($linkedEntity::class !== $linkedEntityReflection->getClass()) {
                throw new InconsistentEntitiesException();
            }
            self::checkIdIsSet(
                $linkedEntityReflection,
                $linkedEntity,
                'Must set ID columns of all entities when attaching'
            );
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
        $data = [];
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
                    $data[$linker->getForeignKey()] = $entity->{$property}->{$linkedEntityId};
                } else {
                    $data[$linker->getForeignKey()] = null;
                }
            }
        }
        return $data;
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

    /**
     * @template D of object
     * @param  EntityReflection<D>  $entityReflection
     * @param  D  $entity
     * @param  string  $message
     * @return void
     */
    private static function checkIdIsSet(EntityReflection $entityReflection, object $entity, string $message): void
    {
        if (!$entityReflection->checkPropertyInitialized($entityReflection->getId(), $entity)) {
            throw new MissingIdException($message);
        }
    }

    private static function checkNumberOfRowsAffected(int $int, string $message): void
    {
        if ($int !== 1) {
            throw new RecordNotFoundException($message);
        }
    }
}
