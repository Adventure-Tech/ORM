<?php

namespace AdventureTech\ORM\Mapping\Relations;

use Illuminate\Support\Collection;

trait ToMany
{
    private string $relation;

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
