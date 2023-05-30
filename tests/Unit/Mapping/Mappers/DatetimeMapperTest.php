<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Mappers;

use AdventureTech\ORM\Mapping\Mappers\DatetimeMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;
use Carbon\CarbonImmutable;
use ReflectionProperty;
use stdClass;

test('The datetime mapper exposes the property name', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = new DatetimeMapper('db_column_name', $property);
    expect($mapper->getPropertyName())->toBe('datetimeProperty');
});

test('The datetime mapper has a single column', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = new DatetimeMapper('db_column_name', $property);
    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['db_column_name']);
});

test('The datetime mapper can check if its property is set on a given entity instance', function (
    MapperTestClass $entity,
    bool $isInitialized
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = new DatetimeMapper('db_column_name', $property);
    expect($mapper->isInitialized($entity))->toBe($isInitialized);
})->with([
    'not initialized' => [fn() => new MapperTestClass(), false],
    'null' => [
        function () {
            $entity = new MapperTestClass();
            $entity->datetimeProperty = null;
            return $entity;
        }, true,
    ],
    'carbon instance' => [
        function () {
            $entity = new MapperTestClass();
            $entity->datetimeProperty = CarbonImmutable::now();
            return $entity;
        },
        true,
    ],
]);

test('The datetime mapper can serialize an entity', function (
    ?CarbonImmutable $value,
    array $expected
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = new DatetimeMapper('db_column_name', $property);
    expect($mapper->serialize($value))
        ->toBeArray()
        ->toEqualCanonicalizing($expected);
})->with([
//    'not initialized' => [fn() => new MapperTestClass(), []],
    'null' => [
        null,
        ['db_column_name' => null],
    ],
    'carbon instance' => [
        CarbonImmutable::parse('2023-01-01 12:00'),
        ['db_column_name' => '2023-01-01T12:00:00+00:00'],
    ],
]);

test('The datetime mapper can deserialize an item with a null value', function (
    stdClass $item,
    string $alias,
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = new DatetimeMapper('db_column_name', $property);
    expect($mapper->deserialize($item, $alias))->toBeNull();
})->with([
    'without alias' => [(object) ['db_column_name' => null], ''],
    'with alias' => [(object) ['aliasdb_column_name' => null], 'alias'],
]);

test('The datetime mapper can deserialize an item with a non-null value', function (
    stdClass $item,
    string $alias,
    string $iso8601String
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = new DatetimeMapper('db_column_name', $property);
    expect($mapper->deserialize($item, $alias))
        ->toBeInstanceOf(CarbonImmutable::class)
        ->toIso8601String()->toBe($iso8601String);
})->with([
    'without alias' => [(object) ['db_column_name' => '2023-01-01T12:00:00+00:00'], '', '2023-01-01T12:00:00+00:00'],
    'with alias' => [(object) ['aliasdb_column_name' => '2023-01-01T12:00:00+00:00'], 'alias', '2023-01-01T12:00:00+00:00'],
    'with non-UTC timezone' => [(object) ['db_column_name' => '2023-01-01T12:00:00+01:00'], '', '2023-01-01T12:00:00+01:00'],
]);
