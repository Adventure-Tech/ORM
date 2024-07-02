<?php

namespace AdventureTech\ORM\Mapping\Linkers;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\EntityNotFoundException;

trait ToOne
{
    public function link(object $currentEntity, ?object $relatedEntity): void
    {
        // skip if received null for non-nullable loaded entity
        if (is_null($relatedEntity) && !EntityReflection::new($currentEntity::class)->allowsNull($this->relation)) {
            throw new EntityNotFoundException(
                sprintf(
                    'Failed to load relation "%s" of entity "%s" with id "%s".',
                    $this->relation,
                    get_class($currentEntity),
                    EntityAccessorService::getId($currentEntity)
                )
            );
        }
        EntityAccessorService::set($currentEntity, $this->relation, $relatedEntity);
    }
}
