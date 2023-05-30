<?php

namespace AdventureTech\ORM\Mapping\Columns;

use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use Attribute;
use Illuminate\Support\Str;
use ReflectionProperty;

/**
 * @implements ColumnAnnotation<string>
 */

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class StringColumnAnnotation implements ColumnAnnotation
{
    /**
     * @param  string|null  $name
     */
    public function __construct(
        private ?string $name = null
    ) {
    }

    /**
     * @param  ReflectionProperty  $property
     * @return DefaultMapper<string>
     */
    public function getMapper(ReflectionProperty $property): DefaultMapper
    {
        return new DefaultMapper(
            $this->name ?? Str::snake($property->getName()),
            $property
        );
    }
}
