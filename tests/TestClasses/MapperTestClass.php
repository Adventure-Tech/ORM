<?php

namespace AdventureTech\ORM\Tests\TestClasses;

use Carbon\CarbonImmutable;

class MapperTestClass
{
    public ?int $intProperty;
    public ?string $stringProperty;
    public ?bool $boolProperty;
    public ?CarbonImmutable $datetimeProperty;
    public ?array $jsonProperty;
}
