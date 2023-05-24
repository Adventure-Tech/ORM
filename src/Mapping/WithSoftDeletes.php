<?php

namespace AdventureTech\ORM\Mapping;

use AdventureTech\ORM\Mapping\Columns\DeletedAtColumn;
use Carbon\CarbonImmutable;

trait WithSoftDeletes
{
    #[DeletedAtColumn]
    public ?CarbonImmutable $deletedAt = null;
}
