<?php

namespace AdventureTech\ORM\Mapping\ManagedColumns;

use Attribute;
use Carbon\CarbonImmutable;

/**
 * @implements ManagedColumnAnnotation<CarbonImmutable>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class UpdatedAt implements ManagedColumnAnnotation
{
    public function getInsertValue(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }

    public function getUpdateValue(mixed $value): CarbonImmutable
    {
        return CarbonImmutable::now();
    }

    public function getDeleteValue(): null
    {
        return null;
    }
}
