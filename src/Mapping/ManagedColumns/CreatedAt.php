<?php

namespace AdventureTech\ORM\Mapping\ManagedColumns;

use Attribute;
use Carbon\CarbonImmutable;
use LogicException;

/**
 * @implements ManagedColumnAnnotation<CarbonImmutable>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class CreatedAt implements ManagedColumnAnnotation
{
    public function getInsertValue(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }

    public function getUpdateValue(mixed $value): ?CarbonImmutable
    {
        if (!is_null($value) && !($value instanceof CarbonImmutable)) {
            // TODO: custom exception
            throw new LogicException('Wrong type passed to managed column');
        }
        return $value;
    }


    public function getDeleteValue(): null
    {
        return null;
    }
}
