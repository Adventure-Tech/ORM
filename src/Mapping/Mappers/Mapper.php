<?php

namespace AdventureTech\ORM\Mapping\Mappers;

use ReflectionProperty;
use stdClass;

/**
 * @template T
 */
interface Mapper
{
    /**
     * @return array<int,string>
     */
    public function getColumnNames(): array;

    /**
     * @param  object  $instance
     * @return bool
     */
    public function isInitialized(object $instance): bool;

    /**
     * @param  object  $entity
     * @return array<string,string|null>
     */
    public function serialize(object $entity): array;

    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @return T|null
     */
    public function deserialize(stdClass $item, string $alias): mixed;
}
