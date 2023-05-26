<?php

namespace AdventureTech\ORM\Mapping\Linkers;

trait ToOne
{
    public function link(object $currentEntity, ?object $relatedEntity): void
    {
        if ($relatedEntity) {
            $currentEntity->{$this->relation} = $relatedEntity;
        }
    }
}
