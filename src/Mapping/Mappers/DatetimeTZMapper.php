<?php

/**
 *
 */

namespace AdventureTech\ORM\Mapping\Mappers;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use Carbon\CarbonImmutable;
use ReflectionProperty;
use stdClass;

/**
 * @implements Mapper<CarbonImmutable>
 */

readonly class DatetimeTZMapper implements Mapper
{
    use WithDefaultMapperMethods;

    /**
     * @param  string  $name
     * @param  string  $tzName
     * @param  ReflectionProperty  $property
     */
    public function __construct(
        private string $name,
        private string $tzName,
        private ReflectionProperty $property
    ) {
    }

    /**
     * @param  CarbonImmutable|null  $value
     * @return array<string,string|null>
     */
    public function serialize(mixed $value): array
    {
        return [
            $this->name => $value?->toIso8601String(),
            $this->tzName => $value?->tzName,
        ];
    }

    /**
     * @param  stdClass  $item
     * @param  LocalAliasingManager  $aliasingManager
     * @return CarbonImmutable|null
     */
    public function deserialize(stdClass $item, LocalAliasingManager $aliasingManager): ?CarbonImmutable
    {
        $datetimeString = $item->{$aliasingManager->getSelectedColumnName($this->name)};
        $tz = $item->{$aliasingManager->getSelectedColumnName($this->tzName)};
        return is_null($datetimeString)
            ? null
            : CarbonImmutable::parse($datetimeString)->setTimezone($tz);
    }
}
