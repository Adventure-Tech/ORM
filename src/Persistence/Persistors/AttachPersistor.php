<?php

namespace AdventureTech\ORM\Persistence\Persistors;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Persistence\Persistors\Dtos\PivotArgsDto;
use AdventureTech\ORM\Persistence\Persistors\Traits\HandlesPivotData;
use Illuminate\Support\Facades\DB;

/**
 * @template TEntity of object
 * @implements Persistor<TEntity>
 */
class AttachPersistor implements Persistor
{
    /**
     * @use HandlesPivotData<TEntity>
     */
    use HandlesPivotData;

    /**
     * @var array<string,string>
     */
    protected array $entityCheckMessages = [
        'checkType' => 'Cannot attach to entity of type "%s" with persistence manager configured for entities of type "%s".',
        'checkIdSet' => 'Must set ID columns when attaching entities.',
        'checkPivotLinker' => 'Can only attach pure many-to-many relations.',
        'checkLinkedEntityType' => 'Cannot attach entity of type "%s" to relation "%s" which links to entities of type "%s".',
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
    public function add(object $entity, array $args = null): self
    {
        $argsDto = PivotArgsDto::parse($args);
        foreach ($this->getPivotData($entity, $argsDto->linkedEntities, $argsDto->relation) as $tableName => $items) {
            foreach ($items as $item) {
                $this->data[$tableName][] = $item;
            }
        }
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
