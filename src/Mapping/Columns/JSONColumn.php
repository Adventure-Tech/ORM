<?php

namespace AdventureTech\ORM\Mapping\Columns;

use AdventureTech\ORM\Mapping\Mappers\JSONMapper;
use Attribute;
use Illuminate\Support\Str;
use ReflectionProperty;

/**
 * @implements Column<array>
 */

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class JSONColumn implements Column
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
     * @return JSONMapper
     */
    public function getMapper(ReflectionProperty $property): JSONMapper
    {
        return new JSONMapper(
            $this->name ?? Str::snake($property->getName()),
            $property
        );
    }
}
