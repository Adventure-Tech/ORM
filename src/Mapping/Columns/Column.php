<?php

/**
 *
 */

namespace AdventureTech\ORM\Mapping\Columns;

use AdventureTech\ORM\ColumnPropertyService;
use AdventureTech\ORM\DefaultNamingService;
use AdventureTech\ORM\Mapping\Mappers\BackedEnumMapper;
use AdventureTech\ORM\Mapping\Mappers\DatetimeMapper;
use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use AdventureTech\ORM\Mapping\Mappers\JSONMapper;
use AdventureTech\ORM\Mapping\Mappers\SimpleMapper;
use AdventureTech\ORM\Mapping\Mappers\UnitEnumMapper;
use Attribute;
use Carbon\CarbonImmutable;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Column implements ColumnAnnotation
{
    /**
     * @param  string|null  $name
     * @param  class-string<SimpleMapper<mixed>>|null  $mapper
     */
    public function __construct(
        private ?string $name = null,
        private ?string $mapper = null
    ) {
    }

    /**
     * @param  ReflectionProperty  $property
     * @return SimpleMapper<mixed>|BackedEnumMapper|UnitEnumMapper|JSONMapper|DatetimeMapper|DefaultMapper
     * @throws ReflectionException
     */
    public function getMapper(ReflectionProperty $property): SimpleMapper|BackedEnumMapper|UnitEnumMapper|JSONMapper|DatetimeMapper|DefaultMapper
    {
        $name = $this->name ?? DefaultNamingService::columnFromProperty($property->getName());

        if (isset($this->mapper)) {
            return new $this->mapper($name);
        }

        /** @var ReflectionNamedType $reflectionNamedType */
        $reflectionNamedType = $property->getType();

        if (ColumnPropertyService::isBackedEnum($property)) {
            return new BackedEnumMapper($name, $property);
        }
        if (ColumnPropertyService::isEnum($property)) {
            return new UnitEnumMapper($name, $property);
        }
        return match ($reflectionNamedType->getName()) {
            CarbonImmutable::class => new DatetimeMapper($name),
            'array' => new JSONMapper($name),
            default => new DefaultMapper($name),
        };
    }
}
