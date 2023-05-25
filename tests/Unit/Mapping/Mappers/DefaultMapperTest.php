<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Mappers;

use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use ReflectionProperty;
use stdClass;

class DefaultMapperTest
{
    public ?string $foo;
}

test('The default mapper exposes the property name', function () {
    $property = new ReflectionProperty(DefaultMapperTest::class, 'foo');
    $mapper = new DefaultMapper('db_column_name', $property);
    expect($mapper->getPropertyName())->toBe('foo');
});

test('The default mapper has a single column', function () {
    $property = new ReflectionProperty(DefaultMapperTest::class, 'foo');
    $mapper = new DefaultMapper('db_column_name', $property);
    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toContain('db_column_name');
});

test('The default mapper can check if its property is set on a given entity instance', function (
    DefaultMapperTest $entity,
    bool $isInitialized
) {
    $property = new ReflectionProperty(DefaultMapperTest::class, 'foo');
    $mapper = new DefaultMapper('db_column_name', $property);
    expect($mapper->isInitialized($entity))->toBe($isInitialized);
})->with([
    'not initialized' => [fn() => new DefaultMapperTest(), false],
    'null' => [
        function () {
            $entity = new DefaultMapperTest();
            $entity->foo = null;
            return $entity;
        }, true,
    ],
    'empty string' => [
        function () {
            $entity = new DefaultMapperTest();
            $entity->foo = '';
            return $entity;
        },
        true,
    ],
]);

test('The default mapper can serialize an entity', function (
    DefaultMapperTest $entity,
    array $expected
) {
    $property = new ReflectionProperty(DefaultMapperTest::class, 'foo');
    $mapper = new DefaultMapper('db_column_name', $property);
    expect($mapper->serialize($entity))
        ->toEqualCanonicalizing($expected);
})->with([
    'not initialized' => [fn() => new DefaultMapperTest(), []],
    'null' => [
        function () {
            $entity = new DefaultMapperTest();
            $entity->foo = null;
            return $entity;
        },
        ['db_column_name' => null],
    ],
    'empty string' => [
        function () {
            $entity = new DefaultMapperTest();
            $entity->foo = '';
            return $entity;
        },
        ['db_column_name' => ''],
    ],
    'non-empty string' => [
        function () {
            $entity = new DefaultMapperTest();
            $entity->foo = 'value';
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
    $property = new ReflectionProperty(DefaultMapperTest::class, 'foo');
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
