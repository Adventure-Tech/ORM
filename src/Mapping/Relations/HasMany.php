<?php

namespace AdventureTech\ORM\Mapping\Relations;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Mapping\Linkers\HasManyLinker;
use AdventureTech\ORM\Mapping\Linkers\ToMany;
use Attribute;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

/**
 * @template ORIGIN of object
 * @template TARGET of object
 * @implements RelationAnnotation<ORIGIN,TARGET>
 */

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class HasMany implements RelationAnnotation
{
    /**
     * @param  class-string<TARGET>  $targetEntity
     * @param  string|null  $foreignKey
     */
    public function __construct(
        private string $targetEntity,
        private ?string $foreignKey = null
    ) {
    }


    /**
     * @param  string  $propertyName
     * @param  string  $propertyType
     * @param  class-string<ORIGIN>  $className
     * @return HasManyLinker<ORIGIN,TARGET>
     */
    public function getLinker(
        string $propertyName,
        string $propertyType,
        string $className,
    ): HasManyLinker {
        return new HasManyLinker(
            originEntity: $className,
            targetEntity: $this->targetEntity,
            relation: $propertyName,
            foreignKey: $this->foreignKey ?? Str::snake(Str::afterLast($className, '\\')) . '_id'
        );
    }
}
