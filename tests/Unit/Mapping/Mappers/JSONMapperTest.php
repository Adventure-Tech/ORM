<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Mappers;

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Exceptions\JSONDeserializationException;
use AdventureTech\ORM\Mapping\Mappers\JSONMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;
use Mockery;
use ReflectionProperty;
use stdClass;

test('The json mapper identifies its type as array', function (JSONMapper $mapper) {
    expect($mapper->getPropertyType())->toBe('array');
})->with('mapper');

test('The json mapper has a single column', function (JSONMapper $mapper) {
    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['db_column_name']);
})->with('mapper');

test('The json mapper can check if its property is set on a given entity instance', function (
    JSONMapper $mapper,
    MapperTestClass $entity,
    bool $isInitialized
) {
    expect($mapper->isInitialized($entity))->toBe($isInitialized);
})
    ->with('mapper')
    ->with([
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
    JSONMapper $mapper,
    ?array $value,
    array $expected
) {
    expect($mapper->serialize($value))
        ->toBeArray()
        ->toEqualCanonicalizing($expected);
})
    ->with('mapper')
    ->with([
        'null' => [null, ['db_column_name' => 'null']],
        'empty array' => [[], ['db_column_name' => '[]']],
        'associative array' => [['x' => true], ['db_column_name' => '{"x":true}']],
        'non-associative array' => [ ['x', 42, true, null], ['db_column_name' => '["x",42,true,null]']],
    ]);

test('The json mapper can deserialize an item with a null value', function (
    JSONMapper $mapper,
    LocalAliasingManager $manager,
    stdClass $item,
) {
    expect($mapper->deserialize($item, $manager))->toBeNull();
})
    ->with('mapper')
    ->with('aliasing manager')
    ->with([
        'null' => [(object) ['db_column_name' => null]],
        'empty string' => [(object) ['db_column_name' => '']],
    ]);

test('The json mapper can deserialize an item with a non-null value', function (
    JSONMapper $mapper,
    LocalAliasingManager $manager,
    stdClass $item,
    array $result
) {
    expect($mapper->deserialize($item, $manager))
        ->toBeArray()
        ->toEqualCanonicalizing($result);
})
    ->with('mapper')
    ->with('aliasing manager')
    ->with([
        'empty object' => [(object) ['db_column_name' => '{}'], []],
        'non-empty object' => [(object) ['db_column_name' => '{"x":42}'], ['x' => 42]],
        'non-empty array' => [(object) ['db_column_name' => '["x",42]'], ['x', 42]],
    ]);

test('The json mapper throws an exception if the item cannot be deserialized to a php array', function (
    JSONMapper $mapper,
    LocalAliasingManager $manager,
    stdClass $item
) {
    expect(fn() =>$mapper->deserialize($item, $manager))->toThrow(
        JSONDeserializationException::class,
        'Invalid JSON deserialized'
    );
})
    ->with('mapper')
    ->with('aliasing manager')
    ->with([
        'true' => [(object) ['db_column_name' => 'true']],
        'false' => [(object) ['db_column_name' => 'false']],
    ]);


dataset('mapper', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'jsonProperty');
    yield new JSONMapper('db_column_name', $property);
});

dataset('aliasing manager', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getSelectedColumnName')
        ->with('db_column_name', 'root')
        ->andReturn('db_column_name');
    yield new LocalAliasingManager($mock, 'root');
});
