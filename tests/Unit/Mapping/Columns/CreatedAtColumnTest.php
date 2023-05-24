<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Columns;

use AdventureTech\ORM\Mapping\Columns\CreatedAtColumn;
use AdventureTech\ORM\Mapping\Columns\DatetimeColumn;
use Carbon\CarbonImmutable;
use ReflectionProperty;

class CreatedAtColumnTest
{
    #[CreatedAtColumn]
    public ?CarbonImmutable $test;
}

test('', function () {
    $column = new CreatedAtColumn();
    $property = new ReflectionProperty(CreatedAtColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('created_at');
});
