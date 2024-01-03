<?php

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Exceptions\EnumSerializationException;
use AdventureTech\ORM\Mapping\Mappers\BackedEnumMapper;
use AdventureTech\ORM\Tests\TestClasses\BackedEnum;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;

it('has a single column', function (BackedEnumMapper $mapper) {
    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['db_column_name']);
})->with('mapper');

it('can serialize an entity', function (
    BackedEnumMapper $mapper,
    ?BackedEnum $value,
    array $expected
) {
    expect($mapper->serialize($value))
        ->toBeArray()
        ->toEqualCanonicalizing($expected);
})
    ->with('mapper')
    ->with([
        'null' => [null, ['db_column_name' => null]],
        'enum value' => [BackedEnum::ONE, ['db_column_name' => 1]],
    ]);

it('throws error when trying to serialize value that is not backed enum', function (
    BackedEnumMapper $mapper,
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
    BackedEnumMapper $mapper,
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
    BackedEnumMapper $mapper,
    LocalAliasingManager $manager,
    stdClass $item,
    BackedEnum $result
) {
    expect($mapper->deserialize($item, $manager))
        ->toEqualCanonicalizing($result);
})
    ->with('mapper')
    ->with('aliasing manager')
    ->with([
        'string value' => [(object) ['db_column_name' => '1'], BackedEnum::ONE],
        'int value' => [(object) ['db_column_name' => 1], BackedEnum::ONE],
    ]);


dataset('mapper', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'backedEnumProperty');
    yield new BackedEnumMapper('db_column_name', $property);
});

dataset('aliasing manager', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getSelectedColumnName')
        ->with('db_column_name', 'root')
        ->andReturn('db_column_name');
    yield new LocalAliasingManager($mock, 'root');
});
