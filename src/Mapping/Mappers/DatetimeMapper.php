<?php

namespace AdventureTech\ORM\Mapping\Mappers;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use Carbon\CarbonImmutable;
use ReflectionProperty;
use stdClass;

/**
 * @implements Mapper<CarbonImmutable>
 */

readonly class DatetimeMapper implements Mapper
{
    use WithDefaultMapperMethods;

    /**
     * @param  CarbonImmutable|null  $value
     * @return array<string,string|null>
     */
    public function serialize(mixed $value): array
    {
        return [$this->name => $value?->toIso8601String()];
    }

    /**
     * @param  stdClass  $item
     * @param  LocalAliasingManager  $aliasingManager
     * @return CarbonImmutable|null
     */
    public function deserialize(stdClass $item, LocalAliasingManager $aliasingManager): ?CarbonImmutable
    {
        $datetimeString = $item->{$aliasingManager->getSelectedColumnName($this->name)};
        return is_null($datetimeString) ? null : CarbonImmutable::parse($datetimeString);
    }
}
