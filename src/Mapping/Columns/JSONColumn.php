<?php

namespace AdventureTech\ORM\Mapping\Columns;

use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use AdventureTech\ORM\Mapping\Mappers\JSONMapper;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use Attribute;
use Illuminate\Support\Str;
use JsonException;
use ReflectionProperty;
use RuntimeException;
use stdClass;

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
     * @return JSONMapper<int>
     */
    public function getMapper(ReflectionProperty $property): JSONMapper
    {
        return new JSONMapper(
            $this->name ?? Str::snake($property->getName()),
            $property
        );
    }
}
