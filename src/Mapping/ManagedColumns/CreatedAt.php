<?php

namespace AdventureTech\ORM\Mapping\ManagedColumns;

use Attribute;
use Carbon\CarbonImmutable;

/**
 * @implements ManagedColumnAnnotation<CarbonImmutable>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class CreatedAt implements ManagedColumnAnnotation
{
    public function getInsertValue(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }

    public function getUpdateValue(): ?CarbonImmutable
    {
        return null;
    }


    public function getDeleteValue(): null
    {
        return null;
    }
}
