<?php

/**
 *
 */

namespace AdventureTech\ORM\Mapping\Columns;

use AdventureTech\ORM\DefaultNamingService;
use AdventureTech\ORM\Mapping\Mappers\DatetimeMapper;
use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use AdventureTech\ORM\Mapping\Mappers\JSONMapper;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use Attribute;
use Carbon\CarbonImmutable;
use ReflectionNamedType;
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
     * @return DefaultMapper<T>|JSONMapper|DatetimeMapper
     */
    public function getMapper(ReflectionProperty $property): Mapper
    {
        $name = $this->name ?? DefaultNamingService::columnFromProperty($property->getName());
        /** @var ReflectionNamedType $reflectionNamedType */
        $reflectionNamedType = $property->getType();
        return match ($reflectionNamedType->getName()) {
            CarbonImmutable::class => new DatetimeMapper($name),
            'array' => new JSONMapper($name),
            default => new DefaultMapper($name),
        };
    }
}
