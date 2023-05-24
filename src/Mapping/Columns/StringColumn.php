<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Attribute;
use stdClass;

/**
 * @implements Column<string>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class StringColumn implements Column
{
    use WithDefaultColumnMethods;

    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @return string|null
     */
    public function deserialize(stdClass $item, string $alias): ?string
    {
        $this->checkInitialized();
        // TODO: what if this is not set?
        return $item->{$alias . $this->name};
    }

    /**
     * @param  object  $entity
     * @return array<string,string|null>
     */
    public function serialize(object $entity): array
    {
        $this->checkInitialized();
        // TODO: what if this is not set?
        return [$this->name => $entity->{$this->getPropertyName()}];
    }
}
