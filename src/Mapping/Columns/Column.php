<?php

namespace AdventureTech\ORM\Mapping\Columns;

use AdventureTech\ORM\Mapping\Mappers\Mapper;
use ReflectionProperty;
use stdClass;

/**
 * @template T
 */
interface Column
{
    /**
     * @param  ReflectionProperty  $property
     * @return Mapper<T>
     */
    public function getMapper(ReflectionProperty $property): Mapper;
}
