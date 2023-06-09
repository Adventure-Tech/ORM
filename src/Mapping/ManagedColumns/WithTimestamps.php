<?php

namespace AdventureTech\ORM\Mapping\ManagedColumns;

use AdventureTech\ORM\Mapping\Columns\Column;
use Carbon\CarbonImmutable;

trait WithTimestamps
{
    #[Column]
    #[CreatedAt]
    public ?CarbonImmutable $createdAt;

    #[Column]
    #[UpdatedAt]
    public ?CarbonImmutable $updatedAt;
}
