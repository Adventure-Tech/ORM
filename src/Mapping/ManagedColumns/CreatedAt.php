<?php

namespace AdventureTech\ORM\Mapping\ManagedColumns;

use AdventureTech\ORM\Exceptions\InvalidTypeException;
use Attribute;
use Carbon\CarbonImmutable;

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
        if (!($value instanceof CarbonImmutable) && !is_null($value)) {
            throw new InvalidTypeException('Wrong type passed to managed column');
        }
        return $value;
    }


    public function getDeleteValue(): null
    {
        return null;
    }
}
