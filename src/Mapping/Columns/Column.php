<?php

namespace AdventureTech\ORM\Mapping\Columns;

use ReflectionProperty;
use stdClass;

interface Column
{
    public function resolveDefault(ReflectionProperty $property): void;
    public function getColumnNames(): array;
    public function getPropertyName(): string;
    public function isInitialized(object $instance): bool;

    public function deserialize(stdClass $item, string $alias): mixed ;
    // TODO: Entity type hint
    public function serialize($entity): array;
}
