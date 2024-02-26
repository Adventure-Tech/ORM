<?php

namespace AdventureTech\ORM\Tests\TestClasses\Entities;

use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Columns\DatetimeTZColumn;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use Carbon\CarbonImmutable;

#[Entity]
class TimezoneEntity
{
    #[Id]
    #[Column]
    public int $id;

    #[Column]
    public CarbonImmutable $datetimeWithoutTz;

    #[DatetimeTZColumn(tzName: 'timezone')]
    public CarbonImmutable $datetimeWithTz;
}
