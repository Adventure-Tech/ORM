<?php

namespace AdventureTech\ORM\Persistence\Persistors;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Mapping\SoftDeletes\SoftDeleteAnnotation;
use AdventureTech\ORM\Persistence\Persistors\Traits\ChecksEntityType;
use AdventureTech\ORM\Persistence\Persistors\Traits\ReflectsEntities;
use AdventureTech\ORM\Persistence\Persistors\Traits\SerializesEntities;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Override;

/**
 * @template Entity of object
 * @implements Persistor<Entity>
 */
class DeletePersistor implements Persistor
{
    /**
     * @use SerializesEntities<Entity>
     */
    use SerializesEntities;
    /**
     * @use ChecksEntityType<Entity>
     */
    use ChecksEntityType;

    /**
     * @var array<string,string>
     */
    protected array $entityCheckMessages = [
        'checkType' => 'Cannot delete entity of type %s with persistence manager configured for entities of type %s.',
        'checkCount' => 'Could not delete all entities. Deleted %d out of %d.',
        'checkIdSet' => 'Must set ID column when deleting entities.',
    ];
    /**
     * @var array<int,int>|array<string|string>
     */
    protected array $ids = [];
    protected bool $isSoftDelete;
    /**
     * @var Collection<string,CarbonImmutable>
     */
    protected Collection $softDeleteDatetimes;

    /**
     * @param  class-string<Entity>  $entityClassName
     * @param  bool  $force
     */
    #[Override]
    public function __construct(string $entityClassName, bool $force = false)
    {
        $this->entityReflection = EntityReflection::new($entityClassName);
        $this->softDeleteDatetimes = $this->entityReflection->getSoftDeletes()
            ->map(fn(SoftDeleteAnnotation $softDelete) => $softDelete->getDatetime());
        $this->isSoftDelete = !$force && !$this->softDeleteDatetimes->isEmpty();
    }

    /**
     * @param  Entity  $entity
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
        foreach ($this->softDeleteDatetimes as $property => $value) {
            EntityAccessorService::set($entity, $property, $value);
        }
        return $this;
    }

    public function persist(): void
    {
        if ($this->isSoftDelete) {
            $data = [];
            $mappers = $this->entityReflection->getMappers();
            foreach ($this->softDeleteDatetimes as $property => $datetime) {
                foreach ($mappers[$property]->serialize($datetime) as $columnName => $value) {
                    $data[$columnName] = $value;
                }
            }
            $deletedRowsCount = DB::table($this->entityReflection->getTableName())
                ->whereIn($this->entityReflection->getIdColumn(), $this->ids)
                ->update($data);
        } else {
            $deletedRowsCount = DB::table($this->entityReflection->getTableName())
                ->whereIn($this->entityReflection->getIdColumn(), $this->ids)
                ->delete();
        }
        $this->checkCount(count($this->ids), $deletedRowsCount);
    }
}
