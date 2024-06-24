<?php

namespace AdventureTech\ORM\Persistence\Persistors;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Mapping\Linkers\Linker;
use AdventureTech\ORM\Persistence\Persistors\Traits\HandlesPivotData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @template TEntity of object
 * @implements Persistor<TEntity>
 */
class DetachPersistor implements Persistor
{
    /**
     * @use HandlesPivotData<TEntity>
     */
    use HandlesPivotData;

    /**
     * @var array<string,string>
     */
    protected array $entityCheckMessages = [
        'checkType' => 'Cannot detach from entity of type "%s" with persistence manager configured for entities of type "%s".',
        'checkIdSet' => 'Must set ID columns when detaching entities.',
        'checkPivotLinker' => 'Can only detach pure many-to-many relations.',
        'checkLinkedEntityType' => 'Cannot detach entity of type "%s" from relation "%s" which links to entities of type "%s".',
    ];
    /**
     * @var array<string,array<int,array<string,int|string>>>
     */
    protected array $data = [];

    /**
     * @param  class-string<TEntity>  $entityClassName
     */
    public function __construct(string $entityClassName)
    {
        $this->entityReflection = EntityReflection::new($entityClassName);
    }

    /**
     * @param  object  $entity
     * @param  array<int,mixed>  $args
     * @return $this
     */
    public function add(object $entity, array $args = null): Persistor
    {
        $argsDto = $this->asd($entity, $args);
        if (EntityAccessorService::isset($entity, $argsDto->relation)) {
            $linkedEntityIds = $argsDto->linkedEntities->mapWithKeys(fn ($entity) => [
                EntityAccessorService::getId($entity) => EntityAccessorService::getId($entity)
            ]);
            /** @var Collection<int|string,object> $currentRelationValue */
            $currentRelationValue = EntityAccessorService::get($entity, $argsDto->relation);
            $newRelationValue = $currentRelationValue->filter(fn($entity) => !EntityAccessorService::issetId($entity)
                || $linkedEntityIds->doesntContain(EntityAccessorService::getId($entity)));
            EntityAccessorService::set($entity, $argsDto->relation, $newRelationValue);
        }
        return $this;
    }

    public function persist(): int
    {
        $count = 0;
        foreach ($this->data as $table => $records) {
            foreach ($records as $record) {
                // TODO: do we need to optimise this by somehow compiling a single DB statement per table?
                $count += DB::table($table)->where($record)->delete();
            }
        }
        return $count;
    }
}
