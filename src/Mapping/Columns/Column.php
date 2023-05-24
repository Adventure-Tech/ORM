<?php

namespace AdventureTech\ORM\Mapping\Columns;

use ReflectionProperty;
use stdClass;

/**
 * @template T
 */
interface Column
{
    /**
     * @param  ReflectionProperty  $property
     * @return void
     */
    public function initialize(ReflectionProperty $property): void;

    /**
     * @return array<int,string>
     */
    public function getColumnNames(): array;

    /**
     * @return string
     */
    public function getPropertyName(): string;

    /**
     * @param  object  $instance
     * @return bool
     */
    public function isInitialized(object $instance): bool;

    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @return T|null
     */
    public function deserialize(stdClass $item, string $alias): mixed;

    /**
     * @param  object  $entity
     * @return array<string,string|null>
     */
    public function serialize(object $entity): array;
}
