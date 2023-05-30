<?php

namespace AdventureTech\ORM\Mapping\ManagedDatetimes;

use Attribute;
use Carbon\CarbonImmutable;

#[Attribute(Attribute::TARGET_PROPERTY)]
class UpdatedAt implements ManagedDatetimeAnnotation
{
    public function getInsertDatetime(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }

    public function getUpdateDatetime(?CarbonImmutable $datetime): CarbonImmutable
    {
        return CarbonImmutable::now();
    }

    public function getDeleteDatetime(): null
    {
        return null;
    }
}
