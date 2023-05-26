<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Mappers;

use AdventureTech\ORM\Mapping\Mappers\JSONMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;
use ReflectionProperty;
use RuntimeException;
use stdClass;

test('The json mapper exposes the property name', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'jsonProperty');
    $mapper = new JSONMapper('db_column_name', $property);
    expect($mapper->getPropertyName())->toBe('jsonProperty');
});

test('The json mapper has a single column', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'jsonProperty');
    $mapper = new JSONMapper('db_column_name', $property);
    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['db_column_name']);
});

test('The json mapper can check if its property is set on a given entity instance', function (
    MapperTestClass $entity,
    bool $isInitialized
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'jsonProperty');
    $mapper = new JSONMapper('db_column_name', $property);
    expect($mapper->isInitialized($entity))->toBe($isInitialized);
})->with([
    'not initialized' => [fn() => new MapperTestClass(), false],
    'null' => [
        function () {
            $entity = new MapperTestClass();
            $entity->jsonProperty = null;
            return $entity;
        }, true,
    ],
    'empty array' => [
        function () {
            $entity = new MapperTestClass();
            $entity->jsonProperty = [];
            return $entity;
        },
        true,
    ],
    'non-empty array' => [
        function () {
            $entity = new MapperTestClass();
            $entity->jsonProperty = ['x' => 12];
            return $entity;
        },
        true,
    ],
]);

test('The json mapper can serialize an entity', function (
    MapperTestClass $entity,
    array $expected
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'jsonProperty');
    $mapper = new JSONMapper('db_column_name', $property);
    expect($mapper->serialize($entity))
        ->toBeArray()
        ->toEqualCanonicalizing($expected);
})->with([
//    'not initialized' => [fn() => new MapperTestClass(), []],
    'null' => [
        function () {
            $entity = new MapperTestClass();
            $entity->jsonProperty = null;
            return $entity;
        },
        ['db_column_name' => 'null'],
    ],
    'empty array' => [
        function () {
            $entity = new MapperTestClass();
            $entity->jsonProperty = [];
            return $entity;
        },
        ['db_column_name' => '[]'],
    ],
    'associative array' => [
        function () {
            $entity = new MapperTestClass();
            $entity->jsonProperty = ['x' => true];
            return $entity;
        },
        ['db_column_name' => '{"x":true}'],
    ],
    'non-associative array' => [
        function () {
            $entity = new MapperTestClass();
            $entity->jsonProperty = ['x', 42, true, null];
            return $entity;
        },
        ['db_column_name' => '["x",42,true,null]'],
    ],
]);

test('The json mapper can deserialize an item with a null value', function (
    stdClass $item,
    string $alias,
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'jsonProperty');
    $mapper = new JSONMapper('db_column_name', $property);
    expect($mapper->deserialize($item, $alias))->toBeNull();
})->with([
    'without alias' => [(object) ['db_column_name' => null], ''],
    'with alias' => [(object) ['aliasdb_column_name' => null], 'alias'],
    'empty string' => [(object) ['db_column_name' => ''], ''],
]);

test('The json mapper can deserialize an item with a non-null value', function (
    stdClass $item,
    string $alias,
    array $result
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'jsonProperty');
    $mapper = new JSONMapper('db_column_name', $property);
    expect($mapper->deserialize($item, $alias))
        ->toBeArray()
        ->toEqualCanonicalizing($result);
})->with([
    'empty array without alias' => [(object) ['db_column_name' => '[]'], '', []],
    'empty object with alias' => [(object) ['aliasdb_column_name' => '{}'], 'alias', []],
    'non-empty object' => [(object) ['aliasdb_column_name' => '{"x":42}'], 'alias', ['x' => 42]],
    'non-empty array' => [(object) ['aliasdb_column_name' => '["x",42]'], 'alias', ['x', 42]],
]);

test('The json mapper throws an exception if the item cannot be deserialized to a php array', function (stdClass $item) {
    $property = new ReflectionProperty(MapperTestClass::class, 'jsonProperty');
    $mapper = new JSONMapper('db_column_name', $property);
    expect(fn() =>$mapper->deserialize($item, ''))->toThrow(
        RuntimeException::class,
        'Invalid JSON deserialized'
    );
})->with([
    'true' => [(object) ['db_column_name' => 'true']],
    'false' => [(object) ['db_column_name' => 'false']],
]);
