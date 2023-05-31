<?php

namespace AdventureTech\ORM;

use Illuminate\Support\Str;

class DefaultNamingService
{
    public static function columnFromProperty(string $property): string
    {
        return Str::snake($property);
    }

    public static function tableFromClass(string $class): string
    {
        return Str::snake(Str::plural(Str::afterLast($class, '\\')));
    }

    public static function foreignKeyFromProperty(string $property): string
    {
        return self::columnFromProperty($property) . '_id';
    }
    public static function foreignKeyFromClass(string $class): string
    {
        return Str::snake(Str::afterLast($class, '\\')) . '_id';
    }
}
