<?php

namespace AdventureTech\ORM\Persistence;

use AdventureTech\ORM\Mapping\Columns\CreatedAtColumn;
use AdventureTech\ORM\Mapping\Columns\DeletedAtColumn;
use AdventureTech\ORM\Mapping\Columns\UpdatedAtColumn;
use AdventureTech\ORM\EntityReflection;
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

        $entityReflection = new EntityReflection(get_class($entity));
        $arr = [];
        foreach ($entityReflection->getColumns() as $property => $column) {
            // TODO: prevent updating created_at and updated_at
            if ($column->isInitialized($entity)) {
                if ($property === $entityReflection->getId()) {
                    throw new RuntimeException('Must not set ID column for insert');
                }
                $arr = array_merge($arr, $column->serialize($entity));
            } elseif ($column instanceof CreatedAtColumn || $column instanceof UpdatedAtColumn) {
                $entity->{$property} = now();
                $arr = array_merge($arr, $column->serialize($entity));
            } elseif ($property !== $entityReflection->getId()) {
                throw new RuntimeException('Forgot to set column without default value [' . $property . ']');
            }
        }

        // TODO: resolve relations

        $id = DB::table($entityReflection->getTableName())->insertGetId($arr);
        $entity->id = $id;
        return $entity;
    }

    /**
     * @param  Collection<mixed,T>  $entities
     *
     * @return T
     */
    public function insertMultiple(Collection $entities): Collection
    {
        return $entities->map(fn ($entity) => self::insert($entity));
    }

    /**
     * @param  T  $entity
     *
     * @return int
     */
    public function update(object $entity): int
    {
        $this->checkType($entity);

        $entityReflection = new EntityReflection(get_class($entity));
        $arr = [];
        if (!isset($entity->{$entityReflection->getId()})) {
            throw new RuntimeException('Must set ID column when updating');
        }
        foreach ($entityReflection->getColumns() as $property => $column) {
            // TODO: prevent updating created_at and updated_at
            if ($column->isInitialized($entity)) {
                $arr = array_merge($arr, $column->serialize($entity));
            } elseif ($column instanceof UpdatedAtColumn) {
                $entity->{$property} = now();
                $arr = array_merge($arr, $column->serialize($entity));
            }
        }

        // TODO: resolve relations

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

        $entityReflection = new EntityReflection(get_class($entity));

        $query = DB::table($entityReflection->getTableName())
            ->where($entityReflection->getId(), '=', $entity->{$entityReflection->getId()});

        foreach ($entityReflection->getColumns() as $column) {
            if ($column instanceof DeletedAtColumn) {
                return $query->update($column->serialize($entity));
            }
        }
        return $query->delete();
    }

    public function attach()
    {
    }

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
}
