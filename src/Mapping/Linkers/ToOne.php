<?php

namespace AdventureTech\ORM\Mapping\Linkers;

use AdventureTech\ORM\EntityAccessorService;

trait ToOne
{
    public function link(object $currentEntity, ?object $relatedEntity): void
    {
        EntityAccessorService::set($currentEntity, $this->relation, $relatedEntity);
    }
}
