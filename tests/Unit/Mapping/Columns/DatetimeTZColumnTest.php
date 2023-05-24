<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Columns;

use AdventureTech\ORM\Exceptions\NotInitializedException;
use AdventureTech\ORM\Mapping\Columns\DatetimeTZColumn;
use Carbon\CarbonImmutable;
use ReflectionProperty;

class DatetimeTZColumnTest
{
    #[DatetimeTZColumn]
    public ?CarbonImmutable $test;
}

test('datetimeTz columns correspond to two DB columns (datetime & timezone) whose names based on property name', function () {
    $column = new DatetimeTZColumn();
    $property = new ReflectionProperty(DatetimeTZColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(2)
        ->toContain('test', 'test_timezone');
});

test('datetimeTz columns correspond to two DB columns (datetime & timezone) that can be customised in the constructor', function () {
    $column = new DatetimeTZColumn('custom', 'customTz');
    $property = new ReflectionProperty(DatetimeTZColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(2)
        ->toContain('custom', 'customTz');
});

test('datetimeTz columns expose their related property name', function () {
    $column = new DatetimeTZColumn('custom');
    $property = new ReflectionProperty(DatetimeTZColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getPropertyName())
        ->toBe('test');
});

test('datetimeTz columns can correctly serialize entities', function () {
    $column = new DatetimeTZColumn('custom');
    $property = new ReflectionProperty(DatetimeTZColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new DatetimeTZColumnTest();
    $entity->test = CarbonImmutable::parse('2023-01-01 12:00')->setTimezone('Europe/Oslo');

    expect($column->serialize($entity))
        ->toBeArray()
        ->toHaveCount(2)
        ->toHaveKeys(['custom', 'custom_timezone'])
        ->and($column->serialize($entity)['custom'])->toBe('2023-01-01T13:00:00+01:00')
        ->and($column->serialize($entity)['custom_timezone'])->toBe('Europe/Oslo');
});

test('trying to serialize an incomplete entity results in exception', function () {
    $column = new DatetimeTZColumn('custom');
    $property = new ReflectionProperty(DatetimeTZColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new DatetimeTZColumnTest();

    expect($column->serialize($entity))
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('custom');
})->todo();

test('datetimeTz columns can correctly deserialize items', function () {
    $column = new DatetimeTZColumn('custom');
    $property = new ReflectionProperty(DatetimeTZColumnTest::class, 'test');
    $column->initialize($property);

    $alias = 'alias_';
    $item = (object)[
        $alias . 'custom' => '2023-01-01 12:00',
        $alias . 'custom_timezone' => '+03:00',
    ];
    expect($column->deserialize($item, $alias))
        ->toBeInstanceOf(CarbonImmutable::class)
        ->toIso8601String()->toBe('2023-01-01T15:00:00+03:00');
});

test('trying to deserialize an incomplete item results in exception', function () {
    $column = new DatetimeTZColumn('custom');
    $property = new ReflectionProperty(DatetimeTZColumnTest::class, 'test');
    $column->initialize($property);

    $alias = 'alias_';
    $item = (object)[
        $alias . 'not_custom' => 'value',
    ];
    expect($column->deserialize($item, $alias))->toBe('value');
})->todo();

test('datetimeTz columns allow checking if entity has initialized value', function () {
    $column = new DatetimeTZColumn('custom');
    $property = new ReflectionProperty(DatetimeTZColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new DatetimeTZColumnTest();

    expect($column->isInitialized($entity))->toBeFalse();
    $entity->test = null;
    expect($column->isInitialized($entity))->toBeTrue();
    $entity->test = CarbonImmutable::now();
    expect($column->isInitialized($entity))->toBeTrue();
});

test('not initialising column throws exception', function () {
    $column = new DatetimeTZColumn();
    expect(fn() => $column->isInitialized(new DatetimeTZColumnTest()))->toThrow(
        NotInitializedException::class,
        'Must initialize before using: AdventureTech\ORM\Mapping\Columns\DatetimeTZColumn'
    )
        ->and(fn() => $column->getColumnNames())->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\DatetimeTZColumn'
        )
        ->and(fn() => $column->getPropertyName())->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\DatetimeTZColumn'
        )
        ->and(fn() => $column->serialize(new DatetimeTZColumnTest()))->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\DatetimeTZColumn'
        )
        ->and(fn() => $column->deserialize((object)[], 'alias'))->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\DatetimeTZColumn'
        );
});
