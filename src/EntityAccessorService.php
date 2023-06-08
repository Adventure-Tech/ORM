<?php

namespace AdventureTech\ORM;

class EntityAccessorService
{
    public static function get(object $entity, string $property): mixed
    {
        return $entity->{$property};
    }

    public static function set(object $entity, string $property, mixed $value): void
    {
        $entity->{$property} = $value;
    }
}
