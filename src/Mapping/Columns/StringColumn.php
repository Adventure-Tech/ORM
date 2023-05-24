<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Attribute;
use stdClass;

#[Attribute(Attribute::TARGET_PROPERTY)]
class StringColumn implements Column
{
    use WithDefaultColumnMethods;

    public function deserialize(stdClass $item, string $alias): ?string
    {
        // TODO: what if this is not set?
        return $item->{$alias . $this->name};
    }

    public function serialize($entity): array
    {
        // TODO: what if this is not set?
        return [$this->name => $entity->{$this->getPropertyName()}];
    }
}
