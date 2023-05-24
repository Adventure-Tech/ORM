<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Columns;

use AdventureTech\ORM\Mapping\Columns\DeletedAtColumn;
use Carbon\CarbonImmutable;
use ReflectionProperty;

class DeletedAtColumnTest
{
    #[DeletedAtColumn]
    public ?CarbonImmutable $test;
}

test('', function () {
    $column = new DeletedAtColumn();
    $property = new ReflectionProperty(DeletedAtColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('deleted_at');
});
