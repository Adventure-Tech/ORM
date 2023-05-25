<?php

namespace AdventureTech\ORM\Mapping;

use AdventureTech\ORM\Mapping\Columns\DatetimeColumn;
use Carbon\CarbonImmutable;

trait WithTimestamps
{
    #[DatetimeColumn]
    #[CreatedAt]
    public ?CarbonImmutable $createdAt = null;

    #[DatetimeColumn]
    #[UpdatedAt]
    public ?CarbonImmutable $updatedAt = null;
}
