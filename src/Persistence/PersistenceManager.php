<?php

namespace AdventureTech\ORM\Persistence;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Mapping\Linkers\BelongsToLinker;
use AdventureTech\ORM\Mapping\Relations\BelongsTo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

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
            throw new LogicException('Need to set $entity when extending');
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
        $this->uncheckedInsert($entity);
        return $entity;
    }

    /**
     * @template key of string|int
     * @param  Collection<key,T>  $entities
     *
     * @return Collection<key,T>
     */
    public function insertMultiple(Collection $entities): Collection
    {
        return $entities->map(fn ($entity) => self::insert($entity));
    }

//    /**
//     * @param  T  $entity
//     *
//     * @return int
//     */
//    public function update(object $entity): int
//    {
//        $this->checkType($entity);
//
//        $entityReflection = new EntityReflection(get_class($entity));
//        $arr = [];
//        if (!isset($entity->{$entityReflection->getId()})) {
//            throw new RuntimeException('Must set ID column when updating');
//        }
//        foreach ($entityReflection->getMappers() as $property => $mapper) {
//            // TODO: prevent updating created_at and updated_at
//            if ($column->isInitialized($entity)) {
//                $arr = array_merge($arr, $column->serialize($entity));
//            } elseif ($column instanceof UpdatedAtColumn) {
//                $entity->{$property} = CarbonImmutable::now();
//                $arr = array_merge($arr, $column->serialize($entity));
//            }
//        }
//
//        // TODO: resolve relations (do we insert? do we even update?)
//
//        return DB::table($entityReflection->getTableName())
//            ->where($entityReflection->getId(), '=', $entity->{$entityReflection->getId()})
//            ->update($arr);
//    }

    /**
     * @param  T  $entity
     *
     * @return int
     */
    public function delete(object $entity): int
    {
        $this->checkType($entity);

        $entityReflection = new EntityReflection(get_class($entity));

        $query = DB::table($entityReflection->getTableName())
            ->where($entityReflection->getId(), '=', $entity->{$entityReflection->getId()});

        $deletedAtMapper = $entityReflection->getDeletedAtMapper();
        if (!is_null($deletedAtMapper)) {
            $now = CarbonImmutable::now();
            $entity->deletedAt = $now;
            return $query->update([
                $deletedAtMapper->getColumnNames()[0] => $now->toIso8601String()
            ]);
        }
        // TODO: what about HasOne/HasMany/BelongsToMany relations?
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
            throw new RuntimeException('Invalid entity type');
        }
    }

    /**
     * @param  T  $entity
     *
     * @return int
     */
    private function uncheckedInsert(object $entity): int
    {
        $entityReflection = new EntityReflection(get_class($entity));
        $arr = [];

        $id = $entityReflection->getId();

        foreach ($entityReflection->getMappers() as $property => $mapper) {
            // TODO: prevent updating created_at and updated_at
            if ($mapper->isInitialized($entity)) {
                if ($property === $id) {
                    throw new RuntimeException('Must not set ID column for insert');
//                    $entityReflection->getCreatedAtColumn() => throw new RuntimeException('Must not set managed CreatedAt column for insert'),
//                    $entityReflection->getUpdatedAtColumn() => throw new RuntimeException('Must not set managed UpdatedAt column for insert'),
//                    $entityReflection->getDeletedAtColumn() => throw new RuntimeException('Must not set managed DeletedAt column for insert'),
//                    default => null
                }
                $arr = array_merge($arr, $mapper->serialize($entity));
            } elseif ($property !== $id) {
                throw new RuntimeException('Forgot to set column without default value [' . $property . ']');
            }
        }

        foreach ($entityReflection->getLinkers() as $property => $relation) {
            if ($relation instanceof BelongsToLinker) {
                $arr[$relation->getForeignKey()] = $this->uncheckedInsert($entity->{$property});
            }
        }


        // TODO: if DB insert fails we will have still updated $entity
        $now = CarbonImmutable::now();
        $createdAtMapper = $entityReflection->getCreatedAtMapper();
        if (!is_null($createdAtMapper)) {
            $arr[$createdAtMapper->getColumnNames()[0]] = $now->toIso8601String();
            $entity->{$createdAtMapper->getPropertyName()} = $now;
        }
        $updatedAtMapper = $entityReflection->getUpdatedAtMapper();
        if (!is_null($updatedAtMapper)) {
            $arr[$updatedAtMapper->getColumnNames()[0]] = $now->toIso8601String();
            $entity->{$updatedAtMapper->getPropertyName()} = $now;
        }
        $deletedAtMapper = $entityReflection->getDeletedAtMapper();
        if (!is_null($deletedAtMapper)) {
            $arr[$deletedAtMapper->getColumnNames()[0]] = null;
            $entity->{$deletedAtMapper->getPropertyName()} = null;
        }

        $id = DB::table($entityReflection->getTableName())->insertGetId($arr);
        $entity->{$entityReflection->getId()} = $id;
        return $id;
    }
}
