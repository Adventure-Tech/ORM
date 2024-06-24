<?php

namespace AdventureTech\ORM\Persistence\Persistors\Traits;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\Exceptions\PersistenceException;
use AdventureTech\ORM\Mapping\Linkers\PivotLinker;
use AdventureTech\ORM\Persistence\Persistors\Dtos\AttachArgsDto;
use Illuminate\Support\Collection;
use TypeError;

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
     * @param  object  $entity
     * @param  mixed  $args
     * @return AttachArgsDto
     */
    private function asd(object $entity, mixed $args): AttachArgsDto
    {
        if (
            !is_array($args)
            || count($args) !== 2
            || !(($args[0] instanceof Collection) || (is_array($args[0])))
            || !is_string($args[1])
        ) {
            // TODO
//            dump(
//                $args,
//                !is_array($args),
//                count($args) !== 2,
//                !(($args[0] instanceof Collection) || (is_array($args[0]))),
//                is_string($args[1] ?? null)
//            );
            throw new TypeError('...');
        }
        [$linkedEntities, $relation] = $args;

        $this->checkType($entity);

        $linker = $this->entityReflection->getLinker($relation);
        if (!($linker instanceof PivotLinker)) {
            throw new PersistenceException($this->entityCheckMessages['checkPivotLinker'] ?? 'Can only handle pure many-to-many relations.');
        }

        $this->checkIdSet($entity);

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
            $this->data[$linker->getPivotTable()][] = [
                $linker->getOriginForeignKey() => $id,
                $linker->getTargetForeignKey() => $linkedId,
            ];
        }
        return new AttachArgsDto(linkedEntities: $linkedEntities, relation: $relation);
    }
}
