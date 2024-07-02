<?php

namespace AdventureTech\ORM\Persistence\Persistors\Traits;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\Exceptions\PersistenceException;
use AdventureTech\ORM\Mapping\Linkers\PivotLinker;
use Illuminate\Support\Collection;

/**
 * @template TEntity of object
 */
trait HandlesPivotData
{
    /**
     * @use ChecksEntityType<TEntity>
     */
    use ChecksEntityType;


    /**
     * @param  TEntity  $entity
     * @param  Collection<int|string,object>  $linkedEntities
     * @param  string  $relation
     * @return array<string,array<int,array<string,int|string>>>
     */
    private function getPivotData(object $entity, Collection $linkedEntities, string $relation): array
    {
        $this->checkType($entity);

        $linker = $this->entityReflection->getLinker($relation);
        if (!($linker instanceof PivotLinker)) {
            throw new PersistenceException($this->entityCheckMessages['checkPivotLinker'] ?? 'Can only handle pure many-to-many relations.');
        }

        $this->checkIdSet($entity);

        $data = [];
        foreach ($linkedEntities as $linkedEntity) {
            if (get_class($linkedEntity) !== $linker->getTargetEntity()) {
                throw new PersistenceException(sprintf(
                    $this->entityCheckMessages['checkLinkedEntityType'] ?? 'Entity of type "%s" is incompatible with relation "%s" which links to entities of type "%s".',
                    get_class($linkedEntity),
                    $relation,
                    $linker->getTargetEntity()
                ));
            }
            $this->checkIdSet($linkedEntity);
            /** @var int|string $id */
            $id = EntityAccessorService::getId($entity);
            /** @var int|string $linkedId */
            $linkedId = EntityAccessorService::getId($linkedEntity);
            $data[$linker->getPivotTable()][] = [
                $linker->getOriginForeignKey() => $id,
                $linker->getTargetForeignKey() => $linkedId,
            ];
        }
        return $data;
    }
}
