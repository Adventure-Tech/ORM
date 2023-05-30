<?php

namespace AdventureTech\ORM\Mapping\Mappers;

use stdClass;

/**
 * @template T
 */
interface Mapper
{
    /**
     * @return string
     */
    public function getPropertyName(): string;

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
     * @param  mixed  $value
     * @return array<string,string|null>
     */
    public function serialize(mixed $value): array;

    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @return T|null
     */
    public function deserialize(stdClass $item, string $alias): mixed;

    public function getType(): string;
}
