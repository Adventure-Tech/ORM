<?php

namespace AdventureTech\ORM\Mapping\Columns;

use AdventureTech\ORM\Mapping\Mappers\DatetimeMapper;
use AdventureTech\ORM\Mapping\Mappers\DatetimeTZMapper;
use Attribute;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use ReflectionProperty;
use stdClass;

/**
 * @implements Column<CarbonImmutable>
 */

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class DatetimeTZColumn implements Column
{
    /**
     * @param  string|null  $name
     * @param  string|null  $tzName
     */
    public function __construct(
        private ?string $name = null,
        private ?string $tzName = null,
    ) {
    }

    /**
     * @param  ReflectionProperty  $property
     * @return DatetimeTZMapper
     */
    public function getMapper(ReflectionProperty $property): DatetimeTZMapper
    {
        $name = $this->name ?? Str::snake($property->getName());
        $tzName = $this->tzName ?? $name . '_timezone';
        return new DatetimeTZMapper($name, $tzName, $property);
    }
}
