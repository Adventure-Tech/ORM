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
    /**
     * @param  string  $name
     * @param  string  $tzName
     */
    public function __construct(
        private string $name,
        private string $tzName,
    ) {
    }

    /**
     * @return array<int,string>
     */
    public function getColumnNames(): array
    {
        return [$this->name, $this->tzName];
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
        $tz = $item->{$aliasingManager->getSelectedColumnName($this->tzName)} ?? 'UTC';
        return is_null($datetimeString)
            ? null
            : CarbonImmutable::parse($datetimeString)->setTimezone($tz);
    }
}
