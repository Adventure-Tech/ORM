<?php

namespace AdventureTech\ORM\Mapping\Mappers;

use AdventureTech\ORM\Exceptions\JSONDeserializationException;
use JsonException;
use ReflectionProperty;
use stdClass;

/**
 * @implements Mapper<array>
 */

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
     * @param  array<mixed,mixed>|null  $value
     * @return array<string,string|null>
     * @throws JsonException
     */
    public function serialize(mixed $value): array
    {
        $json = json_encode($value, JSON_THROW_ON_ERROR);
        return [$this->name => $json];
    }

    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @return array<mixed,mixed>|null
     */
    public function deserialize(stdClass $item, string $alias): array|null
    {
        $json = json_decode($item->{$alias . $this->name}, true);
        if (!is_array($json) && !is_null($json)) {
            throw new JSONDeserializationException();
        }
        return $json;
    }
}
