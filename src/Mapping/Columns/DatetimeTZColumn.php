<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Attribute;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use ReflectionProperty;
use stdClass;

/**
 * @implements Column<CarbonImmutable>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class DatetimeTZColumn implements Column
{
    use WithDefaultColumnMethods;

    private string $name;
    private string $tzName;

    /**
     * @param  string|null  $name
     * @param  string|null  $tzName
     */
    public function __construct(
        string $name = null,
        string $tzName = null,
    ) {
        if (!is_null($name)) {
            $this->name = $name;
        }
        if (!is_null($tzName)) {
            $this->tzName = $tzName;
        }
    }

    public function initialize(ReflectionProperty $property): void
    {
        $this->property = $property;
        if (!isset($this->name)) {
            $this->name = Str::snake($property->getName());
        }
        if (!isset($this->tzName)) {
            $this->tzName = $this->name . '_timezone';
        }
    }

    public function getColumnNames(): array
    {
        return [$this->name, $this->tzName];
    }

    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @return CarbonImmutable|null
     */
    public function deserialize(stdClass $item, string $alias): ?CarbonImmutable
    {
        // TODO: what if this is not set?
        $string = $item->{$alias . $this->name};
        return is_null($string)
            ? null
            : CarbonImmutable::parse($string)->setTimezone($item->{$alias . $this->tzName});
    }

    /**
     * @param  object  $entity
     * @return array<string,string|null>
     */
    public function serialize(object $entity): array
    {
        // TODO: what if this is not set?
        /** @var CarbonImmutable|null $datetime */
        $datetime = $entity->{$this->getPropertyName()};
        return [
            $this->name => $datetime?->toIso8601String(),
            $this->tzName => $datetime?->tzName,
        ];
    }
}
