<?php

namespace AdventureTech\ORM\Mapping\SoftDeletes;

use AdventureTech\ORM\Mapping\Columns\Column;
use Carbon\CarbonImmutable;

trait WithSoftDeletes
{
    #[Column]
    #[DeletedAt]
    public ?CarbonImmutable $deletedAt = null;
}
