<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Columns;

use AdventureTech\ORM\Exceptions\NotInitializedException;
use AdventureTech\ORM\Mapping\Columns\BoolColumn;
use ReflectionProperty;

class BoolColumnTest
{
    #[BoolColumn]
    public ?bool $test;
}

test('bool columns have single column name based on property name', function () {
    $column = new BoolColumn();
    $property = new ReflectionProperty(BoolColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('test');
});

test('bool columns have single column name that can be customised in the constructor', function () {
    $column = new BoolColumn('custom');
    $property = new ReflectionProperty(BoolColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getColumnNames())
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('custom');
});

test('bool columns expose their related property name', function () {
    $column = new BoolColumn('custom');
    $property = new ReflectionProperty(BoolColumnTest::class, 'test');
    $column->initialize($property);
    expect($column->getPropertyName())
        ->toBe('test');
});

test('bool columns can correctly serialize entities', function () {
    $column = new BoolColumn('custom');
    $property = new ReflectionProperty(BoolColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new BoolColumnTest();
    $entity->test = true;

    expect($column->serialize($entity))
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('custom')
        ->and($column->serialize($entity)['custom'])->toBeTrue();
});

test('trying to serialize an incomplete entity results in exception', function () {
    $column = new BoolColumn('custom');
    $property = new ReflectionProperty(BoolColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new BoolColumnTest();

    expect($column->serialize($entity))
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('custom');
})->todo();

test('bool columns can correctly deserialize items', function () {
    $column = new BoolColumn('custom');
    $property = new ReflectionProperty(BoolColumnTest::class, 'test');
    $column->initialize($property);

    $alias = 'alias_';
    $item = (object) [
        $alias . 'custom' => true,
    ];
    expect($column->deserialize($item, $alias))->toBeTrue();
});

test('trying to deserialize an incomplete item results in exception', function () {
    $column = new BoolColumn('custom');
    $property = new ReflectionProperty(BoolColumnTest::class, 'test');
    $column->initialize($property);

    $alias = 'alias_';
    $item = (object) [
        $alias . 'not_custom' => true,
    ];
    expect($column->deserialize($item, $alias))->toBeTrue();
})->todo();

test('bool columns allow checking if entity has initialized value', function () {
    $column = new BoolColumn('custom');
    $property = new ReflectionProperty(BoolColumnTest::class, 'test');
    $column->initialize($property);

    $entity = new BoolColumnTest();

    expect($column->isInitialized($entity))->toBeFalse();
    $entity->test = null;
    expect($column->isInitialized($entity))->toBeTrue();
    $entity->test = true;
    expect($column->isInitialized($entity))->toBeTrue();
    $entity->test = false;
    expect($column->isInitialized($entity))->toBeTrue();
});

test('not initialising column throws exception', function () {
    $column = new BoolColumn();
    expect(fn() => $column->isInitialized(new BoolColumnTest()))->toThrow(
        NotInitializedException::class,
        'Must initialize before using: AdventureTech\ORM\Mapping\Columns\BoolColumn'
    )
        ->and(fn() => $column->getColumnNames())->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\BoolColumn'
        )
        ->and(fn() => $column->getPropertyName())->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\BoolColumn'
        )
        ->and(fn() => $column->serialize(new BoolColumnTest()))->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\BoolColumn'
        )
        ->and(fn() => $column->deserialize((object) [], 'alias'))->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Columns\BoolColumn'
        );
});
