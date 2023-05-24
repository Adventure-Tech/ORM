<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Attribute;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use stdClass;

/**
 * @implements Column<CarbonImmutable>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class DatetimeColumn implements Column
{
    use WithDefaultColumnMethods;

    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @return CarbonImmutable|null
     */
    public function deserialize(stdClass $item, string $alias): ?CarbonImmutable
    {
        // TODO: what if this is not set?
        $string = $item->{$alias . $this->name};

        return is_null($string) ? null : CarbonImmutable::parse($string);
    }

    /**
     * @param  object  $entity
     * @return array<string,string|null>
     */
    public function serialize(object $entity): array
    {
        // TODO: what if this is not set?
        return [$this->name => $entity->{$this->getPropertyName()}?->toIso8601String()];
    }
}
