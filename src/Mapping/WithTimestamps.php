<?php

namespace AdventureTech\ORM\Mapping;

use AdventureTech\ORM\Mapping\Columns\CreatedAtColumn;
use AdventureTech\ORM\Mapping\Columns\UpdatedAtColumn;
use Carbon\CarbonImmutable;

trait WithTimestamps
{
    #[CreatedAtColumn]
    public ?CarbonImmutable $createdAt;

    #[UpdatedAtColumn]
    public ?CarbonImmutable $updatedAt;
}
