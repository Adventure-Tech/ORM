<?php

namespace AdventureTech\ORM\Mapping\Mappers;

use AdventureTech\ORM\Mapping\Columns\Column;
use Attribute;
use JsonException;
use ReflectionProperty;
use RuntimeException;
use stdClass;

/**
 * @implements Column<array>
 */

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class JSONMapper implements Mapper
{
    /**
     * @param  string  $name
     * @param  ReflectionProperty  $property
     */
    public function __construct(
        private string $name,
        private ReflectionProperty $property
    ) {
    }

    /**
     * @return array<int,string>
     */
    public function getColumnNames(): array
    {
        return [$this->name];
    }

    /**
     * @param  object  $instance
     * @return bool
     */
    public function isInitialized(object $instance): bool
    {
        return $this->property->isInitialized($instance);
    }

    /**
     * @param  object  $entity
     * @return array<string,string|null>
     * @throws JsonException
     */
    public function serialize(object $entity): array
    {
        // TODO: what if this is not set?
        $json = json_encode($entity->{$this->property->getName()}, JSON_THROW_ON_ERROR);
        return [$this->name => $json];
    }

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
}
