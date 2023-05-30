<?php

namespace AdventureTech\ORM\Mapping\SoftDeletes;

use AdventureTech\ORM\Mapping\Columns\DatetimeColumnAnnotation;
use Carbon\CarbonImmutable;

trait WithSoftDeletes
{
    #[DatetimeColumnAnnotation]
    #[DeletedAt]
    public ?CarbonImmutable $deletedAt = null;
}
