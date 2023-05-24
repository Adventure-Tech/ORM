<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Attribute;
use RuntimeException;
use stdClass;

/**
 * @implements Column<array>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class JSONColumn implements Column
{
    use WithDefaultColumnMethods;

    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @return array<mixed,mixed>|null
     */
    public function deserialize(stdClass $item, string $alias): array|null
    {
        // TODO: what if this is not set?
        $json = json_decode($item->{$alias . $this->name}, true);
        if (!is_array($json) && !is_null($json)) {
                throw new RuntimeException('Invalid JSON deserialized');
        }
        return $json;
    }

    /**
     * @param  object  $entity
     * @return array<string,string|null>
     */
    public function serialize(object $entity): array
    {
        // TODO: what if this is not set?
        $json = json_encode($entity->{$this->getPropertyName()});
        if ($json === false) {
            // TODO: custom exception + investigate when this happens
            throw new RuntimeException('Could not json_encode');
        }
        return [$this->name => $json];
    }
}
