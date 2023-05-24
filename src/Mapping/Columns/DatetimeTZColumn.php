<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Attribute;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use ReflectionProperty;
use stdClass;

#[Attribute(Attribute::TARGET_PROPERTY)]
class DatetimeTZColumn implements Column
{
    use WithDefaultColumnMethods;

    public function __construct(
        private ?string $name = null,
        private ?string $tzName = null,
    ) {
    }

    public function resolveDefault(ReflectionProperty $property): void
    {
        $this->property = $property;
        if (is_null($this->name)) {
            $this->name = Str::snake($property->getName());
        }
        if (is_null($this->tzName)) {
            $this->tzName = $this->name . '_timezone';
        }
    }

    public function getColumnNames(): array
    {
        return [$this->name, $this->tzName];
    }

    public function deserialize(stdClass $item, string $alias): CarbonImmutable
    {
        // TODO: what if this is not set?
        return CarbonImmutable::parse($item->{$alias . $this->name})
            ->setTimezone($item->{$alias . $this->tzName});
    }

    public function serialize($entity): array
    {
        // TODO: what if this is not set?
        /** @var CarbonImmutable $datetime */
        $datetime = $entity->{$this->getPropertyName()};
        return [
            $this->name => $datetime->toIso8601String(),
            $this->tzName => $datetime->tzName,
        ];
    }
}
