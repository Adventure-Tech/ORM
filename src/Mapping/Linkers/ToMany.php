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
        // TODO: this surely breaks. need better check
        if ($relatedEntity) {
            EntityAccessorService::get($currentEntity, $this->relation)[] = $relatedEntity;
        }
    }
}
