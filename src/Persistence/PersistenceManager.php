<?php

namespace AdventureTech\ORM\Persistence;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\BadlyConfiguredPersistenceManagerException;
use AdventureTech\ORM\Exceptions\CannotRestoreHardDeletedRecordException;
use AdventureTech\ORM\Exceptions\IdSetForInsertException;
use AdventureTech\ORM\Exceptions\InconsistentEntitiesException;
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
use RuntimeException;

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
            if ($property === $id) {
                if (isset($entity->{$property})) {
                    throw new IdSetForInsertException();
                }
            } else {
                if (!$entityReflection->allowsNull($property) && !isset($entity->{$property})) {
                    throw new MissingValueForColumnException($property);
                }
                $data = array_merge($data, $mapper->serialize($entity->{$property} ?? null));
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

        $unsetManagedColumns = [];
        foreach ($entityReflection->getManagedColumns() as $property => $managedColumn) {
            $updateValue = $managedColumn->getUpdateValue();
            if (!is_null($updateValue)) {
                $entity->{$property} = $updateValue;
            } else {
                $unsetManagedColumns[$property] = $property;
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
            if (array_key_exists($property, $unsetManagedColumns)) {
                continue;
            }
            if (!$entityReflection->allowsNull($property) && !isset($entity->{$property})) {
                throw new MissingValueForColumnException($property);
            }
            $data = array_merge($data, $mapper->serialize($entity->{$property} ?? null));
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
     * @param  Collection<int|string,object>|array<int|string,object>  $linkedEntities
     * @param  string  $relation
     * @return int
     */
    public static function attach(object $entity, Collection|array $linkedEntities, string $relation): int
    {
        $entityReflection = self::getEntityReflection($entity);

        $linker = $entityReflection->getLinkers()->get($relation);

        $linkedEntities = Collection::wrap($linkedEntities);

        if (!($linker instanceof PivotLinker)) {
            throw new InvalidRelationException('Can only attach pure many-to-many relations');
        }

        self::checkIdIsSet($entityReflection, $entity, 'Must set ID column on base entity when attaching/detaching');

        $data = self::getPivotData($entityReflection, $entity, $linker, $linkedEntities);

        $entity->{$relation} = $linkedEntities;

        return DB::table($linker->getPivotTable())->insertOrIgnore($data);
    }

    /**
     * @param  T  $entity
     * @param  Collection<int|string,object>|array<int|string,object>  $linkedEntities
     * @param  string  $relation
     * @return int
     */
    public static function detach(object $entity, Collection|array $linkedEntities, string $relation): int
    {
        $entityReflection = self::getEntityReflection($entity);

        $linker = $entityReflection->getLinkers()->get($relation);

        $linkedEntities = Collection::wrap($linkedEntities);

        if (!($linker instanceof PivotLinker)) {
            throw new InvalidRelationException('Can only detach pure many-to-many relations');
        }

        self::checkIdIsSet($entityReflection, $entity, 'Must set ID column on base entity when detaching');

        $data = self::getPivotData($entityReflection, $entity, $linker, $linkedEntities);

        $linkedEntityReflection = EntityReflection::new($linker->getTargetEntity());
        if (isset($entity->{$relation})) {
            $linkedEntityIds = $linkedEntities->pluck(
                $linkedEntityReflection->getId(),
                $linkedEntityReflection->getId()
            );
            $entity->{$relation} = $entity->{$relation}->filter(fn($entity) =>
                !isset($entity->{$linkedEntityReflection->getId()})
                || $linkedEntityIds->doesntContain($entity->{$linkedEntityReflection->getId()}));
        }

        $int = 0;
        foreach ($data as $item) {
            $int += DB::table($linker->getPivotTable())->where($item)->delete();
        }

        return $int;
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
                if (!$entityReflection->allowsNull($property) && !isset($entity->{$property})) {
                    throw new MissingOwningRelationException('Must set all non-nullable owning relations');
                    // case 4: non-nullable and not set
                    //         -> exception
                } elseif (isset($entity->{$property})) {
                    $linkedEntityReflection = EntityReflection::new($linker->getTargetEntity());
                    $linkedEntity = $entity->{$property};
                    $linkedEntityId = $linkedEntityReflection->getId();
                    self::checkIdIsSet($linkedEntityReflection, $linkedEntity, 'Owned linked entity must have valid ID set');
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
        if (!isset($entity->{$entityReflection->getId()})) {
            throw new MissingIdException($message);
        }
    }

    private static function checkNumberOfRowsAffected(int $int, string $message): void
    {
        if ($int !== 1) {
            throw new RecordNotFoundException($message);
        }
    }

    /**
     * @template D of object
     * @param  EntityReflection<D>  $entityReflection
     * @param  D  $entity
     * @param  PivotLinker<D>  $linker
     * @param  Collection<int|string,object>  $linkedEntities
     * @return array<int|string,array<string,mixed>>
     */
    private static function getPivotData(
        EntityReflection $entityReflection,
        object $entity,
        PivotLinker $linker,
        Collection $linkedEntities
    ): array {
        $linkedEntityReflection = EntityReflection::new($linker->getTargetEntity());
        /** @var array<int|string,array<int|string>> $data */
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
                'Must set ID columns of all entities when attaching/detaching'
            );
            return [
                $linker->getOriginForeignKey() => $entity->{$entityReflection->getId()},
                $linker->getTargetForeignKey() => $linkedEntity->{$linkedEntityReflection->getId()},
            ];
        })->toArray();
        return $data;
    }
}
