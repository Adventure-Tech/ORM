<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Attribute;
use stdClass;

/**
 * @implements Column<int>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class IntColumn implements Column
{
    use WithDefaultColumnMethods;

    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @return int|null
     */
    public function deserialize(stdClass $item, string $alias): ?int
    {
        // TODO: what if this is not set?
        return $item->{$alias . $this->name};
    }

    /**
     * @param  object  $entity
     * @return array<string,int|null>
     */
    public function serialize(object $entity): array
    {
        // TODO: what if this is not set?
        return [$this->name => $entity->{$this->getPropertyName()}];
    }
}
