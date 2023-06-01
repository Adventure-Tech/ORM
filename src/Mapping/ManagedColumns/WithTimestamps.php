<?php

namespace AdventureTech\ORM\Mapping\ManagedColumns;

use AdventureTech\ORM\Mapping\Columns\Column;
use Carbon\CarbonImmutable;

trait WithTimestamps
{
    #[Column]
    #[CreatedAt]
    public ?CarbonImmutable $createdAt = null;

    #[Column]
    #[UpdatedAt]
    public ?CarbonImmutable $updatedAt = null;
}
