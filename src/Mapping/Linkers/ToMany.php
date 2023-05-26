<?php

namespace AdventureTech\ORM\Mapping\Linkers;

use Illuminate\Support\Collection;

trait ToMany
{
    public function link(object $currentEntity, ?object $relatedEntity): void
    {
        if (!isset($currentEntity->{$this->relation})) {
            $currentEntity->{$this->relation} = Collection::empty();
        }
        if ($relatedEntity) {
            $currentEntity->{$this->relation}[] = $relatedEntity;
        }
    }
}
