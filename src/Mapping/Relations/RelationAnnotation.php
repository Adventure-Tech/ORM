<?php

namespace AdventureTech\ORM\Mapping\Relations;

use AdventureTech\ORM\Mapping\Linkers\Linker;

/**
 * @template ORIGIN of object
 * @template TARGET of object
 */
interface RelationAnnotation
{
    /**
     * @param  string  $propertyName
     * @param  class-string<TARGET>  $propertyType
     * @param  class-string<ORIGIN>  $className
     * @return Linker<ORIGIN,TARGET>
     */
    public function getLinker(
        string $propertyName,
        string $propertyType,
        string $className
    ): Linker;
}
