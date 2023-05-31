<?php

namespace AdventureTech\ORM\Mapping\Mappers;

use AdventureTech\ORM\ColumnAliasing\LocalAliasingManager;
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
     * @param  ReflectionProperty  $property
     */
    public function __construct(
        private string $name,
        private string $tzName,
        private ReflectionProperty $property
    ) {
    }

    /**
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->property->getName();
    }

    public function getColumnNames(): array
    {
        return [$this->name, $this->tzName];
    }

    /**
     * @param  object  $instance
     * @return bool
     */
    public function isInitialized(object $instance): bool
    {
        return $this->property->isInitialized($instance);
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
        return is_null($datetimeString)
            ? null
            : CarbonImmutable::parse($datetimeString)->setTimezone($item->{$aliasingManager->getSelectedColumnName($this->tzName)});
    }

    public function getType(): string
    {
        return $this->property->getType()->getName();
    }
}
