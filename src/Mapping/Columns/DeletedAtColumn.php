<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class DeletedAtColumn extends DatetimeColumn
{
    public function __construct()
    {
        parent::__construct('deleted_at');
    }
}
