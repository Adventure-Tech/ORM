<?php

namespace AdventureTech\ORM\Persistence\Persistors;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\PersistenceException;
use AdventureTech\ORM\Mapping\SoftDeletes\SoftDeleteAnnotation;
use AdventureTech\ORM\Persistence\Persistors\Traits\ChecksEntityType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @template TEntity of object
 * @implements Persistor<TEntity>
 */
class RestorePersistor implements Persistor
{
    /**
     * @use ChecksEntityType<TEntity>
     */
    use ChecksEntityType;

    /**
     * @var array<string,string>
     */
    protected array $entityCheckMessages = [
        'checkType' => 'Cannot restore entity of type "%s" with persistence manager configured for entities of type "%s".',
        'checkCount' => 'Could not restore all entities. Restored %d out of %d.',
        'checkIdSet' => 'Must set ID column when restoring entities.',
    ];
    /**
     * @var array<int,int>|array<string,string>
     */
    protected array $ids = [];
    /**
     * @var Collection<string,SoftDeleteAnnotation>
     */
    protected Collection $softDeletes;

    /**
     * @param  class-string<TEntity> $entityClassName
     */
    public function __construct(string $entityClassName)
    {
        $this->entityReflection = EntityReflection::new($entityClassName);
        $this->softDeletes = $this->entityReflection->getSoftDeletes();
        if ($this->softDeletes->isEmpty()) {
            throw new PersistenceException('Cannot restore entity without soft-deletes.');
        }
    }


    /**
     * @param  TEntity  $entity
     * @param  array<int,mixed>  $args
     * @return $this
     */
    public function add(object $entity, array $args = null): self
    {
        $this->checkType($entity);
        $this->checkIdSet($entity);
        /** @var int|string $id */
        $id = EntityAccessorService::getId($entity);
        $this->ids[$id] = $id;
        foreach ($this->softDeletes as $property => $softDelete) {
            EntityAccessorService::set($entity, $property, null);
        }
        return $this;
    }

    public function persist(): void
    {
        $data = [];
        $mappers = $this->entityReflection->getMappers();
        foreach ($this->softDeletes as $property => $softDelete) {
            foreach ($mappers[$property]->getColumnNames() as $columnName) {
                $data[$columnName] = null;
            }
        }

        $restoredRowsCount = DB::table($this->entityReflection->getTableName())
            ->whereIn($this->entityReflection->getIdColumn(), $this->ids)
            ->update($data);

        $this->checkCount(count($this->ids), $restoredRowsCount);
    }
}
