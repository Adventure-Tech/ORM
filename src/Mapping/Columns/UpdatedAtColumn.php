<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class UpdatedAtColumn extends DatetimeColumn
{
    public function __construct()
    {
        parent::__construct('updated_at');
    }
}
