<?php

namespace AdventureTech\ORM;

use Illuminate\Support\Str;

/**
 * @template T of object
 */
class EntityAccessorService
{
    public static function get(object $entity, string $property): mixed
    {
        $method = Str::camel('get_' . Str::snake($property));
        if (method_exists($entity, $method)) {
            return $entity->$method();
        } else {
            return $entity->{$property} ?? null;
        }
    }

    public static function getId(object $entity): mixed
    {
        return self::get($entity, EntityReflection::new($entity::class)->getId());
    }

    public static function set(object $entity, string $property, mixed $value): void
    {
        $method = Str::camel('set_' . Str::snake($property));
        if (method_exists($entity, $method)) {
            $entity->$method($value);
        } else {
            $entity->{$property} = $value;
        }
    }

    public static function setId(object $entity, mixed $value): void
    {
        self::set($entity, EntityReflection::new($entity::class)->getId(), $value);
    }

    public static function isset(object $entity, string $property): bool
    {
        return !is_null(self::get($entity, $property));
    }

    public static function issetId(object $entity): bool
    {
        return self::isset($entity, EntityReflection::new($entity::class)->getId());
    }
}