<?php

namespace AdventureTech\ORM\Mapping;

use AdventureTech\ORM\Mapping\Columns\DatetimeColumn;
use Carbon\CarbonImmutable;

trait WithSoftDeletes
{
    #[DatetimeColumn]
    #[DeletedAt]
    public ?CarbonImmutable $deletedAt = null;
}
