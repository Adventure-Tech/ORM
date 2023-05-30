<?php

namespace AdventureTech\ORM\Mapping\SoftDeletes;

use AdventureTech\ORM\Mapping\ManagedDatetimes\ManagedDatetimeAnnotation;
use Attribute;
use Carbon\CarbonImmutable;

#[Attribute(Attribute::TARGET_PROPERTY)]
class DeletedAt implements SoftDeleteAnnotation
{
    public function getDatetime(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }
}
