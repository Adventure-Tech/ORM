<?php

namespace AdventureTech\ORM\Mapping\Relations;

use AdventureTech\ORM\Mapping\Linkers\BelongsToLinker;
use Attribute;
use Illuminate\Support\Str;

/**
 * @template TARGET of object
 * @implements RelationAnnotation<object,TARGET>
 */

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class BelongsTo implements RelationAnnotation
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
     * @param  string  $className
     * @return BelongsToLinker<TARGET>
     */
    public function getLinker(
        string $propertyName,
        string $propertyType,
        string $className
    ): BelongsToLinker {
        return new BelongsToLinker(
            targetEntity: $propertyType,
            relation: $propertyName,
            foreignKey: $this->foreignKey ?? Str::snake($propertyName) . '_id'
        );
    }
}
