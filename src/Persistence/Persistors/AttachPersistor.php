<?php

namespace AdventureTech\ORM\Persistence\Persistors;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Mapping\Linkers\Linker;
use AdventureTech\ORM\Persistence\Persistors\Traits\HandlesPivotData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @template Entity of object
 * @implements Persistor<Entity>
 */
class AttachPersistor implements Persistor
{
    /**
     * @use HandlesPivotData<Entity>
     */
    use HandlesPivotData;

    /**
     * @var array<string,string>
     */
    protected array $entityCheckMessages = [
        'checkType' => 'Cannot attach to entity of type %s with persistence manager configured for entities of type %s.',
        'checkIdSet' => 'Must set ID columns when attaching entities.',
        'checkPivotLinker' => 'Can only attach pure many-to-many relations.',
        'checkLinkedEntityType' => 'Cannot attach entity of type %s to relation "%s" which links to entities of type %s.',
    ];
    /**
     * @var array<string,array<int,array<string,int|string>>>
     */
    protected array $data = [];
    /**
     * @var Collection<string,Linker<Entity,object>>
     */
    protected Collection $linkers;

    /**
     * @param  class-string<Entity>  $entityClassName
     */
    public function __construct(string $entityClassName)
    {
        $this->entityReflection = EntityReflection::new($entityClassName);
        $this->linkers = $this->entityReflection->getLinkers();
    }

    /**
     * @param  object  $entity
     * @param  array<int,mixed>  $args
     * @return $this
     */
    public function add(object $entity, array $args = null): self
    {
        $argsDto = $this->asd($entity, $args);
        EntityAccessorService::set($entity, $argsDto->relation, $argsDto->linkedEntities);
        return $this;
    }

    public function persist(): int
    {
        $count = 0;
        foreach ($this->data as $table => $records) {
            $count += DB::table($table)->insertOrIgnore($records);
        }
        return $count;
    }
}
