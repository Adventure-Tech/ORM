<?php

namespace AdventureTech\ORM\Mapping\Relations;

trait ToOne
{
    private string $relation;

    public function link(object $currentEntity, ?object $relatedEntity): void
    {
        if ($relatedEntity) {
            $currentEntity->{$this->relation} = $relatedEntity;
        }
    }
}
