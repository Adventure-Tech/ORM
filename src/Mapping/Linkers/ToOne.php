<?php

namespace AdventureTech\ORM\Mapping\Linkers;

trait ToOne
{
    public function link(object $currentEntity, ?object $relatedEntity): void
    {
            $currentEntity->{$this->relation} = $relatedEntity;
    }
}
