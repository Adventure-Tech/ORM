<?php

namespace AdventureTech\ORM\Mapping\Mappers;

use ReflectionProperty;
use stdClass;

/**
 * @template T
 * @implements Mapper<T>
 */

readonly class DefaultMapper implements Mapper
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
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->property->getName();
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
     * @return array<string,T|null>
     */
    public function serialize(object $entity): array
    {
        // TODO: what if this is not set?
        return [$this->name => $entity->{$this->property->getName()}];
    }

    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @return T|null
     */
    public function deserialize(stdClass $item, string $alias): mixed
    {
        // TODO: what if this is not set?
        return $item->{$alias . $this->name};
    }
}
