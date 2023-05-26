<?php

namespace AdventureTech\ORM\Mapping\ManagedDatetimes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class UpdatedAt implements ManagedDatetimeAnnotation
{
    public function getManagedDatetime(): ManagedUpdatedAt
    {
        return new ManagedUpdatedAt();
    }
}
