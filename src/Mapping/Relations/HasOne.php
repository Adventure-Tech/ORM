<?php

namespace AdventureTech\ORM\Mapping\Relations;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Mapping\Linkers\HasOneLinker;
use AdventureTech\ORM\Mapping\Linkers\ToOne;
use Attribute;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

/**
 * @template ORIGIN of object
 * @template TARGET of object
 * @implements RelationAnnotation<ORIGIN,TARGET>
 */

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class HasOne implements RelationAnnotation
{
    /**
     * @param  string|null  $foreignKey
     */
    public function __construct(
        private ?string $foreignKey = null
    ) {
    }


    /**
     * @param  string  $propertyName
     * @param  class-string<TARGET>  $propertyType
     * @param  class-string<ORIGIN>  $className
     * @return HasOneLinker<ORIGIN,TARGET>
     */
    public function getLinker(
        string $propertyName,
        string $propertyType,
        string $className,
    ): HasOneLinker {
        return new HasOneLinker(
            originEntity: $className,
            targetEntity: $propertyType,
            relation: $propertyName,
            foreignKey: $this->foreignKey ?? Str::snake(Str::afterLast($className, '\\')) . '_id'
        );
    }
}
