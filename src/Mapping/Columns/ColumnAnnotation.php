<?php

namespace AdventureTech\ORM\Mapping\Columns;

use AdventureTech\ORM\Mapping\Mappers\Mapper;
use ReflectionProperty;

interface ColumnAnnotation
{
    /**
     * @param  ReflectionProperty  $property
     * @return Mapper<mixed>
     */
    public function getMapper(ReflectionProperty $property): Mapper;
}
