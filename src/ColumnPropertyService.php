<?php

namespace AdventureTech\ORM;

use AdventureTech\ORM\Exceptions\EntityReflectionInstantiationException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

class ColumnPropertyService
{
    /**
     * Test if the property of the column being mapped is an enum
     *
     * @param  ReflectionProperty  $property
     * @return bool
     */
    public static function isEnum(ReflectionProperty $property): bool
    {
        if ($property->getType()->isBuiltin()) {
            return false;
        }

        try {
            $reflectionClass = new RefLectionClass($property->getType()->getName());
        } catch (ReflectionException) {
            throw new EntityReflectionInstantiationException($property->getType()->getName());
        }

        return $reflectionClass->isEnum();
    }
}