<?php

namespace AdventureTech\ORM\Mapping\Linkers;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\EntityReflection;

trait ToOne
{
    public function link(object $currentEntity, ?object $relatedEntity): void
    {
        // skip if received null for non-nullable loaded entity
        if (!is_null($relatedEntity) || EntityReflection::new($currentEntity::class)->allowsNull($this->relation)) {
            EntityAccessorService::set($currentEntity, $this->relation, $relatedEntity);
        }
    }
}
