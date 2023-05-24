<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Columns;

use AdventureTech\ORM\Exceptions\NotInitializedException;
use AdventureTech\ORM\Mapping\Columns\IntColumn;
use ReflectionProperty;

class IntColumnTest
{
    #[IntColumn]
    public ?int $test;
}

test('int columns have single column name based on property name', function () {
    $column = new IntColumn();
    $property = new ReflectionProperty(IntColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('test');
});

test('int columns have single column name that can be customised in the constructor', function () {
    $column = new IntColumn('custom');
    $property = new ReflectionProperty(IntColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('custom');
});

test('int columns expose their related property name', function () {
    $column = new IntColumn('custom');
    $property = new ReflectionProperty(IntColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getPropertyName())
        ->toBe('test');
});

test('int columns can correctly serialize entities', function () {
    $column = new IntColumn('custom');
    $property = new ReflectionProperty(IntColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new IntColumnTest();
    $entity->test = 42;

    expect($column->serialize($entity))
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('custom', true);
});

test('trying to serialize an incomplete entity results in exception', function () {
    $column = new IntColumn('custom');
    $property = new ReflectionProperty(IntColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new IntColumnTest();

    expect($column->serialize($entity))
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('custom', true);
})->todo();

test('int columns can correctly deserialize items', function () {
    $column = new IntColumn('custom');
    $property = new ReflectionProperty(IntColumnTest::class, 'test');
    $column->initialize($property);

    $alias = 'alias_';
    $item = (object)[
        $alias . 'custom' => 42,
    ];
    expect($column->deserialize($item, $alias))->toBe(42);
});

test('trying to deserialize an incomplete item results in exception', function () {
    $column = new IntColumn('custom');
    $property = new ReflectionProperty(IntColumnTest::class, 'test');
    $column->initialize($property);

    $alias = 'alias_';
    $item = (object)[
        $alias . 'not_custom' => 42,
    ];
    expect($column->deserialize($item, $alias))->toBe(42);
})->todo();

test('int columns allow checking if entity has initialized value', function () {
    $column = new IntColumn('custom');
    $property = new ReflectionProperty(IntColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new IntColumnTest();

    expect($column->isInitialized($entity))->toBeFalse();
    $entity->test = null;
    expect($column->isInitialized($entity))->toBeTrue();
    $entity->test = 42;
    expect($column->isInitialized($entity))->toBeTrue();
    $entity->test = 0;
    expect($column->isInitialized($entity))->toBeTrue();
});

test('not initialising column throws exception', function () {
    $column = new IntColumn();
    expect(fn() => $column->isInitialized(new IntColumnTest()))->toThrow(
        NotInitializedException::class,
        'Must initialize before using: AdventureTech\ORM\Mapping\Columns\IntColumn'
    )
        ->and(fn() => $column->getColumnNames())->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\IntColumn'
        )
        ->and(fn() => $column->getPropertyName())->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\IntColumn'
        )
        ->and(fn() => $column->serialize(new IntColumnTest()))->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\IntColumn'
        )
        ->and(fn() => $column->deserialize((object)[], 'alias'))->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\IntColumn'
        );
});
