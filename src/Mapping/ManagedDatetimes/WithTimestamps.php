<?php

namespace AdventureTech\ORM\Mapping\ManagedDatetimes;

use AdventureTech\ORM\Mapping\Columns\DatetimeColumnAnnotation;
use Carbon\CarbonImmutable;

trait WithTimestamps
{
    #[DatetimeColumnAnnotation]
    #[CreatedAt]
    public ?CarbonImmutable $createdAt = null;

    #[DatetimeColumnAnnotation]
    #[UpdatedAt]
    public ?CarbonImmutable $updatedAt = null;
}
