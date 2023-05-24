<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Attribute;
use stdClass;

#[Attribute(Attribute::TARGET_PROPERTY)]
class JSONColumn implements Column
{
    use WithDefaultColumnMethods;

    public function deserialize(stdClass $item, string $alias): mixed
    {
        // TODO: what if this is not set?
        return json_decode($item->{$alias . $this->name}, true);
    }

    public function serialize($entity): array
    {
        // TODO: what if this is not set?
        return [$this->name => json_encode($entity->{$this->getPropertyName()})];
    }
}
