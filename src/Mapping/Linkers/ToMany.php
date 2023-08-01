<?php

namespace AdventureTech\ORM\Mapping\Linkers;

use AdventureTech\ORM\EntityAccessorService;
use Illuminate\Support\Collection;

trait ToMany
{
    public function link(object $currentEntity, ?object $relatedEntity): void
    {
        if (!EntityAccessorService::isset($currentEntity, $this->relation)) {
            EntityAccessorService::set($currentEntity, $this->relation, Collection::empty());
        }
        if (!is_null($relatedEntity)) {
            /** @var Collection<int|string,object> $collection */
            $collection = EntityAccessorService::get($currentEntity, $this->relation);
            $id = EntityAccessorService::getId($relatedEntity);
            if (!is_null($id) && !$collection->has($id)) {
                $collection[$id] = $relatedEntity;
            }
        }
    }
}
