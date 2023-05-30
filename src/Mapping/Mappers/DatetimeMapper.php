<?php

namespace AdventureTech\ORM\Mapping\Mappers;

use Carbon\CarbonImmutable;
use ReflectionProperty;
use stdClass;

/**
 * @implements Mapper<CarbonImmutable>
 */

readonly class DatetimeMapper implements Mapper
{
    /**
     * @param  string  $name
     * @param  ReflectionProperty  $property
     */
    public function __construct(
        private string $name,
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

    /**
     * @return array<int,string>
     */
    public function getColumnNames(): array
    {
        return [$this->name];
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
        return [$this->name => $value?->toIso8601String()];
    }
    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @return CarbonImmutable|null
     */
    public function deserialize(stdClass $item, string $alias): ?CarbonImmutable
    {
        $datetimeString = $item->{$alias . $this->name};
        return is_null($datetimeString) ? null : CarbonImmutable::parse($datetimeString);
    }

    public function getType(): string
    {
        return $this->property->getType()->getName();
    }
}
