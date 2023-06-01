<?php

namespace AdventureTech\ORM\Mapping\Columns;

use AdventureTech\ORM\Mapping\Mappers\Mapper;
use ReflectionProperty;

/**
 * @template T
 */
interface ColumnAnnotation
{
    /**
     * @param  ReflectionProperty  $property
     * @return Mapper<T>
     */
    public function getMapper(ReflectionProperty $property): Mapper;
}
