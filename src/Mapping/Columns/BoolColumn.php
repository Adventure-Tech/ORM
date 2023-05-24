<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Attribute;
use stdClass;

/**
 * @implements Column<bool>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class BoolColumn implements Column
{
    use WithDefaultColumnMethods;

    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @return bool|null
     */
    public function deserialize(stdClass $item, string $alias): ?bool
    {
        // TODO: what if this is not set?
        return $item->{$alias . $this->name};
    }

    /**
     * @param  object  $entity
     * @return array<string,bool|null>
     */
    public function serialize(object $entity): array
    {
        // TODO: what if this is not set?
        return [$this->name => $entity->{$this->getPropertyName()}];
    }
}
