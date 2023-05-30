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

    public function getUpdateValue(mixed $value): ?CarbonImmutable
    {
        // TODO: perform type-check
        return $value;
    }


    public function getDeleteValue(): null
    {
        return null;
    }
}
