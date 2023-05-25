<?php

namespace AdventureTech\ORM\Mapping\Columns;

use AdventureTech\ORM\Mapping\Mappers\DatetimeMapper;
use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use Attribute;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use ReflectionProperty;
use stdClass;

/**
 * @implements Column<CarbonImmutable>
 */

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class DatetimeColumn implements Column
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
     * @return DatetimeMapper
     */
    public function getMapper(ReflectionProperty $property): DatetimeMapper
    {
        return new DatetimeMapper(
            $this->name ?? Str::snake($property->getName()),
            $property
        );
    }
}
