<?php

namespace AdventureTech\ORM\Mapping\Columns;

use AdventureTech\ORM\Mapping\Mappers\DatetimeMapper;
use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use AdventureTech\ORM\Mapping\Mappers\JSONMapper;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use Attribute;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use ReflectionProperty;

/**
 * @template T
 * @implements ColumnAnnotation<T>
 */

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Column implements ColumnAnnotation
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
     * @return Mapper<T>
     */
    public function getMapper(ReflectionProperty $property): Mapper
    {
        $name = $this->name ?? Str::snake($property->getName());
        return match ($property->getType()->getName()) {
            CarbonImmutable::class => new DatetimeMapper($name, $property),
            'array' => new JSONMapper($name, $property),
            default => new DefaultMapper($name, $property)
        };
    }
}
