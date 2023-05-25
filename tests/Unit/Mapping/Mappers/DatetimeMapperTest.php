<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Mappers;

use AdventureTech\ORM\Mapping\Mappers\DatetimeMapper;
use Carbon\CarbonImmutable;
use ReflectionProperty;
use stdClass;

class DatetimeMapperTest
{
    public ?CarbonImmutable $foo;
}

test('The datetime mapper exposes the property name', function () {
    $property = new ReflectionProperty(DatetimeMapperTest::class, 'foo');
    $mapper = new DatetimeMapper('db_column_name', $property);
    expect($mapper->getPropertyName())->toBe('foo');
});

test('The datetime mapper has a single column', function () {
    $property = new ReflectionProperty(DatetimeMapperTest::class, 'foo');
    $mapper = new DatetimeMapper('db_column_name', $property);
    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toContain('db_column_name');
});

test('The datetime mapper can check if its property is set on a given entity instance', function (
    DatetimeMapperTest $entity,
    bool $isInitialized
) {
    $property = new ReflectionProperty(DatetimeMapperTest::class, 'foo');
    $mapper = new DatetimeMapper('db_column_name', $property);
    expect($mapper->isInitialized($entity))->toBe($isInitialized);
})->with([
    'not initialized' => [fn() => new DatetimeMapperTest(), false],
    'null' => [
        function () {
            $entity = new DatetimeMapperTest();
            $entity->foo = null;
            return $entity;
        }, true,
    ],
    'carbon instance' => [
        function () {
            $entity = new DatetimeMapperTest();
            $entity->foo = CarbonImmutable::now();
            return $entity;
        },
        true,
    ],
]);

test('The datetime mapper can serialize an entity', function (
    DatetimeMapperTest $entity,
    array $expected
) {
    $property = new ReflectionProperty(DatetimeMapperTest::class, 'foo');
    $mapper = new DatetimeMapper('db_column_name', $property);
    expect($mapper->serialize($entity))
        ->toEqualCanonicalizing($expected);
})->with([
    'not initialized' => [fn() => new DatetimeMapperTest(), []],
    'null' => [
        function () {
            $entity = new DatetimeMapperTest();
            $entity->foo = null;
            return $entity;
        },
        ['db_column_name' => null],
    ],
    'carbon instance' => [
        function () {
            $entity = new DatetimeMapperTest();
            $entity->foo = CarbonImmutable::parse('2023-01-01 12:00');
            return $entity;
        },
        ['db_column_name' => '2023-01-01T12:00:00+00:00'],
    ],
]);

test('The datetime mapper can deserialize an item with a null value', function (
    stdClass $item,
    string $alias,
) {
    $property = new ReflectionProperty(DatetimeMapperTest::class, 'foo');
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
    $property = new ReflectionProperty(DatetimeMapperTest::class, 'foo');
    $mapper = new DatetimeMapper('db_column_name', $property);
    expect($mapper->deserialize($item, $alias))
        ->toBeInstanceOf(CarbonImmutable::class)
        ->toIso8601String()->toBe($iso8601String);
})->with([
    'without alias' => [(object) ['db_column_name' => '2023-01-01T12:00:00+00:00'], '', '2023-01-01T12:00:00+00:00'],
    'with alias' => [(object) ['aliasdb_column_name' => '2023-01-01T12:00:00+00:00'], 'alias', '2023-01-01T12:00:00+00:00'],
    'with non-UTC timezone' => [(object) ['db_column_name' => '2023-01-01T12:00:00+01:00'], '', '2023-01-01T12:00:00+01:00'],
]);
