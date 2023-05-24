<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Columns;

use AdventureTech\ORM\Mapping\Columns\UpdatedAtColumn;
use Carbon\CarbonImmutable;
use ReflectionProperty;

class UpdatedAtColumnTest
{
    #[UpdatedAtColumn]
    public ?CarbonImmutable $test;
}

test('', function () {
    $column = new UpdatedAtColumn();
    $property = new ReflectionProperty(UpdatedAtColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('updated_at');
});
