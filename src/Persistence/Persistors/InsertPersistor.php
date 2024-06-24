<?php

namespace AdventureTech\ORM\Persistence\Persistors;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\Exceptions\PersistenceException;
use AdventureTech\ORM\Persistence\Persistors\Traits\ChecksEntityType;
use AdventureTech\ORM\Persistence\Persistors\Traits\SerializesEntities;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * @template T of object
 * @implements Persistor<T>
 */
class InsertPersistor implements Persistor
{
    /**
     * @use SerializesEntities<T>
     */
    use SerializesEntities;
    /**
     * @use ChecksEntityType<T>
     */
    use ChecksEntityType;

    /**
     * @var array<string,string>
     */
    protected array $entityCheckMessages = [
        'checkType' => 'Cannot insert entity of type "%s" with persistence manager configured for entities of type "%s".',
        'checkIdSet' => 'Must set ID column when inserting entities.',
    ];
    /**
     * @var array<int,T>
     */
    protected array $entities = [];
    /**
     * @var array<int,array<string,mixed>>
     */
    protected array $values = [];

    /**
     * @param  T  $entity
     * @param  array<int,mixed>  $args
     * @return $this
     */
    public function add(object $entity, array $args = null): self
    {
        $this->checkType($entity);
        if ($this->entityReflection->hasAutogeneratedId() && EntityAccessorService::issetId($entity)) {
            throw new PersistenceException('Must not set autogenerated ID column when inserting entities.');
        }
        if (!$this->entityReflection->hasAutogeneratedId() && !EntityAccessorService::issetId($entity)) {
            throw new PersistenceException('Must set non-autogenerated ID column when inserting entities.');
        }
        foreach ($this->entityReflection->getManagedColumns() as $property => $managedColumn) {
            EntityAccessorService::set($entity, $property, $managedColumn->getInsertValue());
        }
        foreach ($this->entityReflection->getSoftDeletes() as $property => $softDelete) {
            EntityAccessorService::set($entity, $property, null);
        }
        $this->entities[] = $entity;
        $this->values[] = $this->serializeEntity($entity, false);
        return $this;
    }

    public function persist(): void
    {
        if (count($this->entities) === 0) {
            return;
        }
        if ($this->entityReflection->hasAutogeneratedId()) {
            $query = DB::table($this->entityReflection->getTableName());
            $sql = $query->grammar->compileInsert($query, $this->values) . ' RETURNING ' . $this->entityReflection->getIdColumn();
            $ids = array_map(
                fn(stdClass $item) => $item->{$this->entityReflection->getIdColumn()},
                $query->connection->select($sql, $query->cleanBindings(Arr::flatten($this->values, 1)))
            );
            foreach ($ids as $index => $id) {
                EntityAccessorService::setId($this->entities[$index], $id);
            }
        } else {
            DB::table($this->entityReflection->getTableName())->insert($this->values);
        }
    }
}
