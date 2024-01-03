<?php

namespace AdventureTech\ORM;

use AdventureTech\ORM\Exceptions\UnsupportedReflectionTypeException;
use BackedEnum;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;

class ColumnPropertyService
{
    /**
     * Check if the Property is nullable
     *
     * @param  ReflectionProperty  $property
     * @return bool
     */
    public static function allowsNull(ReflectionProperty $property): bool
    {
        return self::getReflectionType($property)->allowsNull();
    }

    /**
     * Get the default value of the Property
     *
     * @param  ReflectionProperty  $property
     * @return mixed
     */
    public static function getDefaultValue(ReflectionProperty $property): mixed
    {
        return $property->getDefaultValue();
    }

    /**
     * Return the property type name
     *
     * @param  ReflectionProperty  $property
     * @return string
     */
    public static function getPropertyType(ReflectionProperty $property): string
    {
        return self::getReflectionType($property)->getName();
    }

    /**
     * Check if the Property is an enum (backed or not)
     *
     * @param  ReflectionProperty  $property
     * @return bool
     * @throws ReflectionException
     */
    public static function isEnum(ReflectionProperty $property): bool
    {
        $type = self::getReflectionType($property);
        if ($type->isBuiltin()) {
            return false;
        }

        /** @var class-string $className */
        $className = $type->getName();
        $reflectionClass = new ReflectionClass($className);

        return $reflectionClass->isEnum();
    }

    /**
     * Check if the Property is a backed enum
     *
     * @param  ReflectionProperty  $property
     * @return bool
     * @throws ReflectionException
     */
    public static function isBackedEnum(ReflectionProperty $property): bool
    {
        $type = self::getReflectionType($property);
        if ($type->isBuiltin()) {
            return false;
        }

        /** @var class-string $className */
        $className = $type->getName();
        $reflectionClass = new ReflectionClass($className);

        return $reflectionClass->implementsInterface(BackedEnum::class);
    }

    private static function getReflectionType(ReflectionProperty $property): ReflectionNamedType
    {
        $type = $property->getType();
        if (!($type instanceof ReflectionNamedType)) {
            throw new UnsupportedReflectionTypeException();
        }
        return $type;
    }
}
