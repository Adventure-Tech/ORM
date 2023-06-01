<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Mappers;

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;
use Mockery;
use ReflectionProperty;
use stdClass;

test('The datetime mapper identifies its type correctly', function (DefaultMapper $mapper) {
    expect($mapper->getPropertyType())->toBe('string');
})->with('mapper');

test('The default mapper has a single column', function (DefaultMapper $mapper) {
    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['db_column_name']);
})->with('mapper');

test('The default mapper can check if its property is set on a given entity instance', function (
    DefaultMapper $mapper,
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
    DefaultMapper $mapper,
    ?string $value,
    array $expected
) {
    expect($mapper->serialize($value))
        ->toBeArray()
        ->toEqualCanonicalizing($expected);
})
    ->with('mapper')
    ->with([
        'null' => [null, ['db_column_name' => null]],
        'empty string' => ['', ['db_column_name' => '']],
        'non-empty string' => ['value', ['db_column_name' => 'value']],
    ]);

test('The default mapper can deserialize an item', function (
    DefaultMapper $mapper,
    LocalAliasingManager $manager,
    stdClass $item,
    ?string $result
) {
    expect($mapper->deserialize($item, $manager))->toBe($result);
})
    ->with('mapper')
    ->with('aliasing manager')
    ->with([
        'null' => [(object) ['db_column_name' => null], null],
        'empty string' => [(object) ['db_column_name' => ''], ''],
        'non-empty string' => [(object) ['db_column_name' => 'value'], 'value'],
    ]);


dataset('mapper', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'stringProperty');
    yield new DefaultMapper('db_column_name', $property);
});

dataset('aliasing manager', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getSelectedColumnName')
        ->with('db_column_name', 'root')
        ->andReturn('db_column_name');
    yield new LocalAliasingManager($mock, 'root');
});
