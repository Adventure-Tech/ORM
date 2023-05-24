<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class CreatedAtColumn extends DatetimeColumn
{
    public function __construct()
    {
        parent::__construct('created_at');
    }
}
