<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Columns;

use AdventureTech\ORM\Exceptions\NotInitializedException;
use AdventureTech\ORM\Mapping\Columns\StringColumn;
use ReflectionProperty;

class StringColumnTest
{
    #[StringColumn]
    public ?string $test;
}

test('string columns have single column name based on property name', function () {
    $column = new StringColumn();
    $property = new ReflectionProperty(StringColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('test');
});

test('string columns have single column name that can be customised in the constructor', function () {
    $column = new StringColumn('custom');
    $property = new ReflectionProperty(StringColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('custom');
});

test('string columns expose their related property name', function () {
    $column = new StringColumn('custom');
    $property = new ReflectionProperty(StringColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getPropertyName())
        ->toBe('test');
});

test('string columns can correctly serialize entities', function () {
    $column = new StringColumn('custom');
    $property = new ReflectionProperty(StringColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new StringColumnTest();
    $entity->test = 'value';

    expect($column->serialize($entity))
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('custom', true);
});

test('trying to serialize an incomplete entity results in exception', function () {
    $column = new StringColumn('custom');
    $property = new ReflectionProperty(StringColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new StringColumnTest();

    expect($column->serialize($entity))
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('custom', true);
})->todo();

test('string columns can correctly deserialize items', function () {
    $column = new StringColumn('custom');
    $property = new ReflectionProperty(StringColumnTest::class, 'test');
    $column->initialize($property);

    $alias = 'alias_';
    $item = (object)[
        $alias . 'custom' => 'value',
    ];
    expect($column->deserialize($item, $alias))->toBeTrue();
});

test('trying to deserialize an incomplete item results in exception', function () {
    $column = new StringColumn('custom');
    $property = new ReflectionProperty(StringColumnTest::class, 'test');
    $column->initialize($property);

    $alias = 'alias_';
    $item = (object)[
        $alias . 'not_custom' => 'value',
    ];
    expect($column->deserialize($item, $alias))->toBeTrue();
})->todo();

test('string columns allow checking if entity has initialized value', function () {
    $column = new StringColumn('custom');
    $property = new ReflectionProperty(StringColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new StringColumnTest();

    expect($column->isInitialized($entity))->toBeFalse();
    $entity->test = null;
    expect($column->isInitialized($entity))->toBeTrue();
    $entity->test = 'value';
    expect($column->isInitialized($entity))->toBeTrue();
    $entity->test = '';
    expect($column->isInitialized($entity))->toBeTrue();
});


test('not initialising column throws exception', function () {
    $column = new StringColumn();
    expect(fn() => $column->isInitialized(new StringColumnTest()))->toThrow(
        NotInitializedException::class,
        'Must initialize before using: AdventureTech\ORM\Mapping\Columns\StringColumn'
    )
        ->and(fn() => $column->getColumnNames())->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\StringColumn'
        )
        ->and(fn() => $column->getPropertyName())->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\StringColumn'
        )
        ->and(fn() => $column->serialize(new StringColumnTest()))->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\StringColumn'
        )
        ->and(fn() => $column->deserialize((object)[], 'alias'))->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\StringColumn'
        );
});
