<?php

namespace AdventureTech\ORM\Mapping\ManagedDatetimes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class CreatedAt implements ManagedDatetimeAnnotation
{
    public function getManagedDatetime(): ManagedCreatedAt
    {
        return new ManagedCreatedAt();
    }
}
