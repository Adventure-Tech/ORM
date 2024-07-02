<?php

namespace AdventureTech\ORM\Persistence\Persistors;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Persistence\Persistors\Dtos\PivotArgsDto;
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
     * @param  TEntity  $entity
     * @param  array<int,mixed>  $args
     * @return $this
     */
    public function add(object $entity, array $args = null): Persistor
    {
        $argsDto = PivotArgsDto::parse($args);
        foreach ($this->getPivotData($entity, $argsDto->linkedEntities, $argsDto->relation) as $tableName => $items) {
            foreach ($items as $item) {
                $this->data[$tableName][] = $item;
            }
        }
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
                $count += DB::table($table)->where($record)->delete();
            }
        }
        return $count;
    }
}
