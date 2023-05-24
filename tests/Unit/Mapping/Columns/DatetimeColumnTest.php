<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Columns;

use AdventureTech\ORM\Exceptions\NotInitializedException;
use AdventureTech\ORM\Mapping\Columns\DatetimeColumn;
use Carbon\CarbonImmutable;
use ReflectionProperty;

class DatetimeColumnTest
{
    #[DatetimeColumn]
    public ?CarbonImmutable $test;
}

test('datetime columns have single column name based on property name', function () {
    $column = new DatetimeColumn();
    $property = new ReflectionProperty(CreatedAtColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('test');
});

test('datetime columns have single column name that can be customised in the constructor', function () {
    $column = new DatetimeColumn('custom');
    $property = new ReflectionProperty(CreatedAtColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('custom');
});

test('datetime columns expose their related property name', function () {
    $column = new DatetimeColumn('custom');
    $property = new ReflectionProperty(CreatedAtColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getPropertyName())
        ->toBe('test');
});

test('datetime columns can correctly serialize entities', function () {
    $column = new DatetimeColumn('custom');
    $property = new ReflectionProperty(CreatedAtColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new CreatedAtColumnTest();
    $entity->test = CarbonImmutable::parse('2023-01-01 12:00');

    expect($column->serialize($entity))
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('custom')
        ->and($column->serialize($entity)['custom'])->toBe('2023-01-01T12:00:00+00:00');
});

test('trying to serialize an incomplete entity results in exception', function () {
    $column = new DatetimeColumn('custom');
    $property = new ReflectionProperty(CreatedAtColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new CreatedAtColumnTest();

    expect($column->serialize($entity))
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('custom');
})->todo();

test('datetime columns can correctly deserialize items', function () {
    $column = new DatetimeColumn('custom');
    $property = new ReflectionProperty(CreatedAtColumnTest::class, 'test');
    $column->initialize($property);

    $alias = 'alias_';
    $item = (object)[
        $alias . 'custom' => '2023-01-01 12:00',
    ];
    expect($column->deserialize($item, $alias))
        ->toBeInstanceOf(CarbonImmutable::class)
    ->toIso8601String()->toBe('2023-01-01T12:00:00+00:00');
});

test('trying to deserialize an incomplete item results in exception', function () {
    $column = new DatetimeColumn('custom');
    $property = new ReflectionProperty(CreatedAtColumnTest::class, 'test');
    $column->initialize($property);

    $alias = 'alias_';
    $item = (object)[
        $alias . 'not_custom' => 'value',
    ];
    expect($column->deserialize($item, $alias))->toBe('value');
})->todo();

test('datetime columns allow checking if entity has initialized value', function () {
    $column = new DatetimeColumn('custom');
    $property = new ReflectionProperty(CreatedAtColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new CreatedAtColumnTest();

    expect($column->isInitialized($entity))->toBeFalse();
    $entity->test = null;
    expect($column->isInitialized($entity))->toBeTrue();
    $entity->test = CarbonImmutable::now();
    expect($column->isInitialized($entity))->toBeTrue();
});

test('not initialising column throws exception', function () {
    $column = new DatetimeColumn();
    expect(fn() => $column->isInitialized(new CreatedAtColumnTest()))->toThrow(
        NotInitializedException::class,
        'Must initialize before using: AdventureTech\ORM\Mapping\Columns\DatetimeColumn'
    )
        ->and(fn() => $column->getColumnNames())->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\DatetimeColumn'
        )
        ->and(fn() => $column->getPropertyName())->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\DatetimeColumn'
        )
        ->and(fn() => $column->serialize(new CreatedAtColumnTest()))->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\DatetimeColumn'
        )
        ->and(fn() => $column->deserialize((object)[], 'alias'))->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\DatetimeColumn'
        );
});
