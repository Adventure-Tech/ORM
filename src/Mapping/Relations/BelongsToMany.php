<?php

namespace AdventureTech\ORM\Mapping\Relations;

use AdventureTech\ORM\Mapping\Linkers\BelongsToManyLinker;
use Attribute;
use Illuminate\Support\Str;

/**
 * @template ORIGIN of object
 * @template TARGET of object
 * @implements Relation<ORIGIN,TARGET>
 */

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class BelongsToMany implements Relation
{
    /**
     * @param  class-string<TARGET>  $targetEntity
     * @param  string  $pivotTable
     * @param  string|null  $originForeignKey
     * @param  string|null  $targetForeignKey
     */
    public function __construct(
        private string $targetEntity,
        private string $pivotTable,
        private ?string $originForeignKey = null,
        private ?string $targetForeignKey = null
    ) {
    }

    /**
     * @param  string  $propertyName
     * @param  string  $propertyType
     * @param  class-string<ORIGIN>  $className
     * @return BelongsToManyLinker
     */
    public function getLinker(
        string $propertyName,
        string $propertyType,
        string $className,
    ): BelongsToManyLinker {
        return new BelongsToManyLinker(
            targetEntity: $this->targetEntity,
            originEntity: $className,
            relation: $propertyName,
            pivotTable: $this->pivotTable,
            originForeignKey: $this->originForeignKey ?? Str::snake(Str::afterLast($className, '\\')) . '_id',
            targetForeignKey: $this->targetForeignKey ?? Str::snake(Str::afterLast($this->targetEntity, '\\')) . '_id'
        );
    }
}
