<?php

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Exceptions\MapperException;
use AdventureTech\ORM\Mapping\Mappers\EnumMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;
use AdventureTech\ORM\Tests\TestClasses\UnitEnum;

it('has a single column', function (EnumMapper $mapper) {
    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['db_column_name']);
})->with('mapper');

it('can serialize an entity', function (
    EnumMapper $mapper,
    ?UnitEnum $value,
    array $expected
) {
    expect($mapper->serialize($value))
        ->toBeArray()
        ->toEqualCanonicalizing($expected);
})
    ->with('mapper')
    ->with([
        'null' => [null, ['db_column_name' => null]],
        'enum value' => [UnitEnum::A, ['db_column_name' => 'A']],
    ]);

it('throws error when trying to serialize value that is not backed enum', function (
    EnumMapper $mapper,
    mixed $value,
    string $message,
) {
    expect(fn() => $mapper->serialize($value))
        ->toThrow(MapperException::class, $message);
})
    ->with('mapper')
    ->with([
        'string value' => ['foo', 'Only native Enum can be serialized. Attempted serialization of type "string".'],
        'object value' => [(object) ['foo' => 'bar'], 'Only native Enum can be serialized. Attempted serialization of type "stdClass".'],
    ]);

it('can deserialize an item with a null value', function (
    EnumMapper $mapper,
    LocalAliasingManager $manager,
    stdClass $item,
) {
    expect($mapper->deserialize($item, $manager))->toBeNull();
})
    ->with('mapper')
    ->with('aliasing manager')
    ->with([
        'null' => [(object) ['db_column_name' => null]],
    ]);

it('can deserialize an item with a non-null value', function (
    EnumMapper $mapper,
    LocalAliasingManager $manager,
    stdClass $item,
    UnitEnum $result
) {
    expect($mapper->deserialize($item, $manager))->toEqualCanonicalizing($result);
})
    ->with('mapper')
    ->with('aliasing manager')
    ->with([
        [(object) ['db_column_name' => 'A'], UnitEnum::A],
    ]);


dataset('mapper', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'unitEnumProperty');
    yield new EnumMapper('db_column_name', $property);
});

dataset('aliasing manager', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getSelectedColumnName')
        ->with('db_column_name', 'root')
        ->andReturn('db_column_name');
    yield new LocalAliasingManager($mock, 'root');
});
