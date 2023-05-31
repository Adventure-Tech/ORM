<?php

namespace AdventureTech\ORM\Mapping\Linkers;

trait ToOne
{
    public function link(object $currentEntity, ?object $relatedEntity): void
    {
        // TODO: resolve this
//        if ($relatedEntity) {
            $currentEntity->{$this->relation} = $relatedEntity;
//        }
    }
}
