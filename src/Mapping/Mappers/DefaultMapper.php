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
     * @param  T|null  $value
     * @return array<string,T|null>
     */
    public function serialize(mixed $value): array
    {
        return [$this->name => $value];
    }

    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @return T|null
     */
    public function deserialize(stdClass $item, string $alias): mixed
    {
        return $item->{$alias . $this->name};
    }

    public function getType(): string
    {
        return $this->property->getType()->getName();
    }
}
