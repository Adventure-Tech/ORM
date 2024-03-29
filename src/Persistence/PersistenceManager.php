<?php

namespace AdventureTech\ORM\Persistence;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\BadlyConfiguredPersistenceManagerException;
use AdventureTech\ORM\Exceptions\CannotRestoreHardDeletedRecordException;
use AdventureTech\ORM\Exceptions\IdSetForInsertException;
use AdventureTech\ORM\Exceptions\InconsistentEntitiesException;
use AdventureTech\ORM\Exceptions\InvalidEntityTypeException;
use AdventureTech\ORM\Exceptions\InvalidRelationException;
use AdventureTech\ORM\Exceptions\MissingIdValueException;
use AdventureTech\ORM\Exceptions\MissingOwningRelationException;
use AdventureTech\ORM\Exceptions\MissingValueForColumnException;
use AdventureTech\ORM\Exceptions\RecordNotFoundException;
use AdventureTech\ORM\Mapping\Linkers\OwningLinker;
use AdventureTech\ORM\Mapping\Linkers\PivotLinker;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
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

    /**
     * @codeCoverageIgnore
     */
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

        $idProperty = $entityReflection->getIdProperty();

        $hasAutogeneratedId = $entityReflection->hasAutogeneratedId();
        if ($hasAutogeneratedId && EntityAccessorService::issetId($entity)) {
            throw new IdSetForInsertException();
        }
        if (!$hasAutogeneratedId) {
            self::checkIdIsSet($entity, 'Must set non-autogenerated ID column when inserting');
        }

        foreach ($entityReflection->getManagedColumns() as $property => $managedColumn) {
            EntityAccessorService::set($entity, $property, $managedColumn->getInsertValue());
        }
        foreach ($entityReflection->getSoftDeletes() as $property => $softDelete) {
            EntityAccessorService::set($entity, $property, null);
        }
        foreach ($entityReflection->getMappers() as $property => $mapper) {
            if ($hasAutogeneratedId && $property === $idProperty) {
                continue;
            }
            if (!$entityReflection->allowsNull($property) && !EntityAccessorService::isset($entity, $property)) {
                throw new MissingValueForColumnException($property);
            }
            $data = array_merge($data, $mapper->serialize(EntityAccessorService::get($entity, $property)));
        }

        $data = array_merge($data, self::resolveOwningRelations($entity, $entityReflection));

        $query = DB::table($entityReflection->getTableName());
        if ($hasAutogeneratedId) {
            $id = self::insertGetId($query, $entityReflection->getIdColumn(), $data);
            EntityAccessorService::setId($entity, $id);
        } else {
            $query->insert($data);
        }
    }

    /**
     * @param  Builder  $query
     * @param  string  $idColumn
     * @param  array<mixed,mixed>  $values
     * @return int|string
     */
    private static function insertGetId(Builder $query, string $idColumn, array $values): int|string
    {
        $response = $query->connection
            ->select(
                $query->grammar->compileInsert($query, $values) . ' RETURNING ' . $idColumn,
                $query->cleanBindings(Arr::flatten($values, 1))
            );
        return $response[0]->{$idColumn};
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

        self::checkIdIsSet($entity, 'Must set ID column when updating');

        $query = DB::table($entityReflection->getTableName())
            ->where($entityReflection->getIdColumn(), '=', EntityAccessorService::getId($entity));

        $unsetManagedColumns = [];
        foreach ($entityReflection->getManagedColumns() as $property => $managedColumn) {
            $updateValue = $managedColumn->getUpdateValue();
            if (!is_null($updateValue)) {
                EntityAccessorService::set($entity, $property, $updateValue);
            } else {
                $unsetManagedColumns[$property] = $property;
            }
        }

        foreach ($entityReflection->getSoftDeletes() as $property => $softDelete) {
            EntityAccessorService::set($entity, $property, null);
            // TODO: this is a duplicate
            /** @var Mapper<mixed> $mapper */
            $mapper = $entityReflection->getMappers()->get($property);
            $query->whereNull($mapper->getColumnNames()[0]);
        }

        foreach ($entityReflection->getMappers() as $property => $mapper) {
            if (array_key_exists($property, $unsetManagedColumns)) {
                continue;
            }
            if (!$entityReflection->allowsNull($property) && !EntityAccessorService::isset($entity, $property)) {
                throw new MissingValueForColumnException($property);
            }
            $data = array_merge($data, $mapper->serialize(EntityAccessorService::get($entity, $property)));
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
        self::internalDelete($entity);
    }

    /**
     * @param  T  $entity
     *
     * @return void
     */
    public static function forceDelete(object $entity): void
    {
        $entityReflection = self::getEntityReflection($entity);
        self::checkIdIsSet($entity, 'Must set ID column when deleting');
        $int = DB::table($entityReflection->getTableName())
            ->where($entityReflection->getIdColumn(), '=', EntityAccessorService::getId($entity))
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

        self::checkIdIsSet($entity, 'Must set ID column when restoring');

        $data = [];
        foreach ($entityReflection->getSoftDeletes() as $property => $softDelete) {
            /** @var Mapper<mixed> $mapper */
            $mapper = $entityReflection->getMappers()->get($property);
            EntityAccessorService::set($entity, $property, null);
            $data = array_merge($data, $mapper->serialize(null));
        }
        if ($data) {
            $int = DB::table($entityReflection->getTableName())
                ->where($entityReflection->getIdColumn(), '=', EntityAccessorService::getId($entity))
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

        self::checkIdIsSet($entity, 'Must set ID column on base entity when attaching/detaching');

        $data = self::getPivotData($entityReflection, $entity, $linker, $linkedEntities);

        EntityAccessorService::set($entity, $relation, $linkedEntities);

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

        self::checkIdIsSet($entity, 'Must set ID column on base entity when detaching');

        $data = self::getPivotData($entityReflection, $entity, $linker, $linkedEntities);

        if (EntityAccessorService::isset($entity, $relation)) {
            $linkedEntityIds = $linkedEntities->mapWithKeys(fn ($entity) => [
                EntityAccessorService::getId($entity) => EntityAccessorService::getId($entity)
            ]);
            /** @var Collection<int|string,object> $currentRelationValue */
            $currentRelationValue = EntityAccessorService::get($entity, $relation);
            $newRelationValue = $currentRelationValue->filter(fn($entity) => !EntityAccessorService::issetId($entity)
                || $linkedEntityIds->doesntContain(EntityAccessorService::getId($entity)));
            EntityAccessorService::set($entity, $relation, $newRelationValue);
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
                if (!$entityReflection->allowsNull($property) && !EntityAccessorService::isset($entity, $property)) {
                    throw new MissingOwningRelationException('Must set all non-nullable owning relations');
                    // case 4: non-nullable and not set
                    //         -> exception
                } elseif (EntityAccessorService::isset($entity, $property)) {
                    /** @var object $linkedEntity */
                    $linkedEntity = EntityAccessorService::get($entity, $property);
                    self::checkIdIsSet($linkedEntity, 'Owned linked entity must have valid ID set');
                    $data[$linker->getForeignKey()] = EntityAccessorService::getId($linkedEntity);
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
     * @param  object  $entity
     * @param  string  $message
     * @return void
     */
    private static function checkIdIsSet(object $entity, string $message): void
    {
        if (!EntityAccessorService::issetId($entity)) {
            throw new MissingIdValueException($message);
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
            $entity,
            $linker,
            $linkedEntityReflection
        ) {
            if ($linkedEntity::class !== $linkedEntityReflection->getClass()) {
                throw new InconsistentEntitiesException();
            }
            self::checkIdIsSet($linkedEntity, 'Must set ID columns of all entities when attaching/detaching');
            return [
                $linker->getOriginForeignKey() => EntityAccessorService::getId($entity),
                $linker->getTargetForeignKey() => EntityAccessorService::getId($linkedEntity),
            ];
        })->toArray();
        return $data;
    }

    /**
     * @param  T  $entity
     * @param  CarbonImmutable|null  $deletedAt
     * @return void
     */
    protected static function internalDelete(object $entity, CarbonImmutable $deletedAt = null): void
    {
        $entityReflection = self::getEntityReflection($entity);

        self::checkIdIsSet($entity, 'Must set ID column when deleting');

        $data = [];
        foreach ($entityReflection->getSoftDeletes() as $property => $softDelete) {
            /** @var Mapper<mixed> $mapper */
            $mapper = $entityReflection->getMappers()->get($property);
            $deletedAt = $deletedAt ?? $softDelete->getDatetime();
            EntityAccessorService::set($entity, $property, $deletedAt);
            $data = array_merge($data, $mapper->serialize($deletedAt));
        }

        if ($data) {
            $int = DB::table($entityReflection->getTableName())
                ->where($entityReflection->getIdColumn(), '=', EntityAccessorService::getId($entity))
                ->update($data);
        } else {
            $int = DB::table($entityReflection->getTableName())
                ->where($entityReflection->getIdColumn(), '=', EntityAccessorService::getId($entity))
                ->delete();
        }
        self::checkNumberOfRowsAffected($int, 'Could not delete entity');
    }
}
