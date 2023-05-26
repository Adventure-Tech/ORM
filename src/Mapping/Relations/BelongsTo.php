<?php

namespace AdventureTech\ORM\Mapping\Relations;

use AdventureTech\ORM\Mapping\Linkers\BelongsToLinker;
use Attribute;
use Illuminate\Support\Str;

/**
 * @template ORIGIN of object
 * @template TARGET of object
 * @implements Relation<ORIGIN,TARGET>
 */

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class BelongsTo implements Relation
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
     * @return BelongsToLinker
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
