<?php

namespace AdventureTech\ORM\Mapping\ManagedDatetimes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class DeletedAt implements ManagedDatetimeAnnotation
{
    public function getManagedDatetime(): ManagedDeletedAt
    {
        return new ManagedDeletedAt();
    }
}
