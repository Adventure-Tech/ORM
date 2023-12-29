<?php

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Exceptions\EnumSerializationException;
use AdventureTech\ORM\Mapping\Mappers\UnitEnumMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;
use AdventureTech\ORM\Tests\TestClasses\UnitEnum;

it('has a single column', function (UnitEnumMapper $mapper) {
    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['db_column_name']);
})->with('mapper');

it('can serialize an entity', function (
    UnitEnumMapper $mapper,
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
    UnitEnumMapper $mapper,
    mixed $value,
    array $expected
) {
    expect($mapper->serialize($value))
        ->toBeArray()
        ->toEqualCanonicalizing($expected);
})
    ->with('mapper')
    ->with([
        'string value' => ['foo', ['db_column_name' => null]],
        'object value' => [(object) ['foo' => 'bar'], ['db_column_name' => null]],
    ])->throws(EnumSerializationException::class);

it('can deserialize an item with a null value', function (
    UnitEnumMapper $mapper,
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
    UnitEnumMapper $mapper,
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
    yield new UnitEnumMapper('db_column_name', $property);
});

dataset('aliasing manager', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getSelectedColumnName')
        ->with('db_column_name', 'root')
        ->andReturn('db_column_name');
    yield new LocalAliasingManager($mock, 'root');
});
