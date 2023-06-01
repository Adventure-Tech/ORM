<?php

namespace AdventureTech\ORM\Persistence;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\IdSetForInsertException;
use AdventureTech\ORM\Exceptions\InvalidEntityTypeException;
use AdventureTech\ORM\Exceptions\MissingBelongsToRelationException;
use AdventureTech\ORM\Exceptions\MissingIdForUpdateException;
use AdventureTech\ORM\Exceptions\MissingValueForColumnException;
use AdventureTech\ORM\Mapping\Linkers\BelongsToLinker;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use Illuminate\Support\Facades\DB;

use function array_merge;
use function get_class;

/**
 * @template T of object
 */
abstract class BasePersistenceManager
{
    //protected array $rules = [];
    /**
     * @var EntityReflection<T>
     */
    protected EntityReflection $entityReflection;

    /**
     * @param  class-string<T>  $entity
     */
    protected function __construct(protected string $entity)
    {
        $this->entityReflection = EntityReflection::new($this->entity);
    }

    /**
     * @param  T  $entity
     *
     * @return T
     */
    protected function insert(object $entity): object
    {
        $this->checkType($entity);
        $arr = [];

        $id = $this->entityReflection->getId();

        foreach ($this->entityReflection->getManagedColumns() as $property => $managedColumn) {
            $entity->{$property} = $managedColumn->getInsertValue();
        }

        foreach ($this->entityReflection->getSoftDeletes() as $property => $softDelete) {
            $entity->{$property} = null;
        }

        foreach ($this->entityReflection->getMappers() as $property => $mapper) {
            if ($mapper->isInitialized($entity)) {
                if ($property === $id) {
                    throw new IdSetForInsertException();
                }
                $arr = array_merge($arr, $mapper->serialize($entity->{$property}));
            } elseif ($property !== $id) {
                throw new MissingValueForColumnException($property);
            }
        }

        $arr = array_merge($arr, $this->resolveBelongsToRelation($entity));

        $id = DB::table($this->entityReflection->getTableName())->insertGetId($arr);
        $entity->{$this->entityReflection->getId()} = $id;
        return $entity;
    }

    /**
     * @param  T  $entity
     *
     * @return int
     */
    protected function update(object $entity): int
    {
        $this->checkType($entity);
        $arr = [];

        if (!isset($entity->{$this->entityReflection->getId()})) {
            throw new MissingIdForUpdateException();
        }

        foreach ($this->entityReflection->getManagedColumns() as $property => $managedColumn) {
            $entity->{$property} = $managedColumn->getUpdateValue($entity->{$property} ?? null);
        }

        foreach ($this->entityReflection->getSoftDeletes() as $property => $softDelete) {
            $entity->{$property} = null;
        }

        foreach ($this->entityReflection->getMappers() as $property => $mapper) {
            if ($mapper->isInitialized($entity)) {
                $arr = array_merge($arr, $mapper->serialize($entity->{$property}));
            }
        }

        $arr = array_merge($arr, $this->resolveBelongsToRelation($entity));

        // TODO: filter on soft-delete columns?
        return DB::table($this->entityReflection->getTableName())
            ->where($this->entityReflection->getId(), '=', $entity->{$this->entityReflection->getId()})
            ->update($arr);
    }

    /**
     * @param  T  $entity
     *
     * @return int
     */
    protected function delete(object $entity): int
    {
        $this->checkType($entity);
        $query = DB::table($this->entityReflection->getTableName())
            ->where($this->entityReflection->getId(), '=', $entity->{$this->entityReflection->getId()});

        foreach ($this->entityReflection->getSoftDeletes() as $property => $softDelete) {
            /** @var Mapper<mixed> $mapper */
            $mapper = $this->entityReflection->getMappers()->get($property);
            $datetime = $softDelete->getDatetime();
            return $query->update($mapper->serialize($datetime));
        }

        return $query->delete();
    }

//    public function attach()
//    {
//    }


    /**
     * @param  object  $entity
     * @return array<string,mixed>
     */
    private function resolveBelongsToRelation(object $entity): array
    {
        $arr = [];
        foreach ($this->entityReflection->getLinkers() as $property => $linker) {
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
}
