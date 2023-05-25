<?php

namespace AdventureTech\ORM\Mapping\Columns;

use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use Attribute;
use Illuminate\Support\Str;
use ReflectionProperty;
use stdClass;

/**
 * @implements Column<bool>
 */

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class BoolColumn implements Column
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
     * @return DefaultMapper<bool>
     */
    public function getMapper(ReflectionProperty $property): DefaultMapper
    {
        return new DefaultMapper(
            $this->name ?? Str::snake($property->getName()),
            $property
        );
    }
}
