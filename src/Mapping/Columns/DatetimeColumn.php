<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Attribute;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use stdClass;

#[Attribute(Attribute::TARGET_PROPERTY)]
class DatetimeColumn implements Column
{
    use WithDefaultColumnMethods;

    public function deserialize(stdClass $item, string $alias): CarbonImmutable
    {
        // TODO: what if this is not set?
        return CarbonImmutable::parse($item->{$alias . $this->name});
    }

    public function serialize($entity): array
    {
        // TODO: what if this is not set?
        return [$this->name => $entity->{$this->getPropertyName()}->toIso8601String()];
    }
}
