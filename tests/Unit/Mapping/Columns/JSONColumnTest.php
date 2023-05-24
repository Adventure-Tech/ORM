<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Columns;

use AdventureTech\ORM\Exceptions\NotInitializedException;
use AdventureTech\ORM\Mapping\Columns\JSONColumn;
use ReflectionProperty;

class JSONColumnTest
{
    #[JSONColumn]
    public ?array $test;
}

test('json columns have single column name based on property name', function () {
    $column = new JSONColumn();
    $property = new ReflectionProperty(JSONColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('test');
});

test('json columns have single column name that can be customised in the constructor', function () {
    $column = new JSONColumn('custom');
    $property = new ReflectionProperty(JSONColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('custom');
});

test('json columns expose their related property name', function () {
    $column = new JSONColumn('custom');
    $property = new ReflectionProperty(JSONColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getPropertyName())
        ->toBe('test');
});

test('json columns can correctly serialize entities', function () {
    $column = new JSONColumn('custom');
    $property = new ReflectionProperty(JSONColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new JSONColumnTest();
    $entity->test = ['foo' => 'bar'];

    expect($column->serialize($entity))
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('custom')
        ->and($column->serialize($entity)['custom'])->toBe('{"foo":"bar"}');
});

test('trying to serialize an incomplete entity results in exception', function () {
    $column = new JSONColumn('custom');
    $property = new ReflectionProperty(JSONColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new JSONColumnTest();

    expect($column->serialize($entity))
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('custom');
})->todo();

test('json columns can correctly deserialize items', function () {
    $column = new JSONColumn('custom');
    $property = new ReflectionProperty(JSONColumnTest::class, 'test');
    $column->initialize($property);

    $alias = 'alias_';
    $item = (object)[
        $alias . 'custom' => '{"foo":"bar"}',
    ];
    expect($column->deserialize($item, $alias))
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('foo', 'bar');
});

test('trying to deserialize an incomplete item results in exception', function () {
    $column = new JSONColumn('custom');
    $property = new ReflectionProperty(JSONColumnTest::class, 'test');
    $column->initialize($property);

    $alias = 'alias_';
    $item = (object)[
        $alias . 'not_custom' => 42,
    ];
    expect($column->deserialize($item, $alias))->toBe(42);
})->todo();

test('json columns allow checking if entity has initialized value', function () {
    $column = new JSONColumn('custom');
    $property = new ReflectionProperty(JSONColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new JSONColumnTest();

    expect($column->isInitialized($entity))->toBeFalse();
    $entity->test = null;
    expect($column->isInitialized($entity))->toBeTrue();
    $entity->test = [];
    expect($column->isInitialized($entity))->toBeTrue();
    $entity->test = ['foo' => 'bar'];
    expect($column->isInitialized($entity))->toBeTrue();
});

test('not initialising column throws exception', function () {
    $column = new JSONColumn();
    expect(fn() => $column->isInitialized(new JSONColumnTest()))->toThrow(
        NotInitializedException::class,
        'Must initialize before using: AdventureTech\ORM\Mapping\Columns\JSONColumn'
    )
        ->and(fn() => $column->getColumnNames())->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\JSONColumn'
        )
        ->and(fn() => $column->getPropertyName())->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\JSONColumn'
        )
        ->and(fn() => $column->serialize(new JSONColumnTest()))->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\JSONColumn'
        )
        ->and(fn() => $column->deserialize((object)[], 'alias'))->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\JSONColumn'
        );
});
