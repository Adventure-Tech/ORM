<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Mappers;

use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;
use ReflectionProperty;
use stdClass;

test('The default mapper exposes the property name', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'stringProperty');
    $mapper = new DefaultMapper('db_column_name', $property);
    expect($mapper->getPropertyName())->toBe('stringProperty');
});

test('The default mapper has a single column', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'stringProperty');
    $mapper = new DefaultMapper('db_column_name', $property);
    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['db_column_name']);
});

test('The default mapper can check if its property is set on a given entity instance', function (
    MapperTestClass $entity,
    bool $isInitialized
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'stringProperty');
    $mapper = new DefaultMapper('db_column_name', $property);
    expect($mapper->isInitialized($entity))->toBe($isInitialized);
})->with([
    'not initialized' => [fn() => new MapperTestClass(), false],
    'null' => [
        function () {
            $entity = new MapperTestClass();
            $entity->stringProperty = null;
            return $entity;
        }, true,
    ],
    'empty string' => [
        function () {
            $entity = new MapperTestClass();
            $entity->stringProperty = '';
            return $entity;
        },
        true,
    ],
]);

test('The default mapper can serialize an entity', function (
    MapperTestClass $entity,
    array $expected
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'stringProperty');
    $mapper = new DefaultMapper('db_column_name', $property);
    expect($mapper->serialize($entity))
        ->toBeArray()
        ->toEqualCanonicalizing($expected);
})->with([
//    'not initialized' => [fn() => new MapperTestClass(), []],
    'null' => [
        function () {
            $entity = new MapperTestClass();
            $entity->stringProperty = null;
            return $entity;
        },
        ['db_column_name' => null],
    ],
    'empty string' => [
        function () {
            $entity = new MapperTestClass();
            $entity->stringProperty = '';
            return $entity;
        },
        ['db_column_name' => ''],
    ],
    'non-empty string' => [
        function () {
            $entity = new MapperTestClass();
            $entity->stringProperty = 'value';
            return $entity;
        },
        ['db_column_name' => 'value'],
    ],
]);

test('The default mapper can deserialize an item', function (
    stdClass $item,
    string $alias,
    ?string $result
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'stringProperty');
    $mapper = new DefaultMapper('db_column_name', $property);
    expect($mapper->deserialize($item, $alias))->toBe($result);
})->with([
    'null without alias' => [(object) ['db_column_name' => null], '', null],
    'empty string without alias' => [(object) ['db_column_name' => ''], '', ''],
    'non-empty string without alias' => [(object) ['db_column_name' => 'value'], '', 'value'],
    'null with alias' => [(object) ['aliasdb_column_name' => null], 'alias', null],
    'empty string with alias' => [(object) ['aliasdb_column_name' => ''], 'alias', ''],
    'non-empty string with alias' => [(object) ['aliasdb_column_name' => 'value'], 'alias', 'value'],
]);

// TODO: column not set in item --> Exception (also other mappers)
// TODO: call isInitialized on non-compatible entity (also other mappers)
