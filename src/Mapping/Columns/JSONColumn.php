<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Attribute;
use JsonException;
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
        $this->checkInitialized();
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
     * @throws JsonException
     */
    public function serialize(object $entity): array
    {
        $this->checkInitialized();
        // TODO: what if this is not set?
        $json = json_encode($entity->{$this->getPropertyName()}, JSON_THROW_ON_ERROR);
        return [$this->name => $json];
    }
}
