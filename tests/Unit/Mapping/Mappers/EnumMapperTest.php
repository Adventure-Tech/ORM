<?php

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Mapping\Mappers\EnumMapper;
use AdventureTech\ORM\Tests\TestClasses\IntEnum;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;

test('The enum mapper has a single column', function (EnumMapper $mapper) {
    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['db_column_name']);
})->with('mapper');

test('The enum mapper can serialize an entity', function (
    EnumMapper $mapper,
    ?IntEnum $value,
    array $expected
) {
    expect($mapper->serialize($value))
        ->toBeArray()
        ->toEqualCanonicalizing($expected);
})
    ->with('mapper')
    ->with([
        'null' => [null, ['db_column_name' => null]],
        'enum value' => [IntEnum::ONE, ['db_column_name' => 1]],
    ]);

test('The enum mapper can deserialize an item with a null value', function (
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

test('The json mapper can deserialize an item with a non-null value', function (
    EnumMapper $mapper,
    LocalAliasingManager $manager,
    stdClass $item,
    IntEnum $result
) {
    expect($mapper->deserialize($item, $manager))
        ->toEqualCanonicalizing($result);
})
    ->with('mapper')
    ->with('aliasing manager')
    ->with([
        'string value' => [(object) ['db_column_name' => '1'], IntEnum::ONE],
        'int value' => [(object) ['db_column_name' => 1], IntEnum::ONE],
    ]);

dataset('mapper', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'enumProperty');
    yield new EnumMapper('db_column_name', $property);
});

dataset('aliasing manager', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getSelectedColumnName')
        ->with('db_column_name', 'root')
        ->andReturn('db_column_name');
    yield new LocalAliasingManager($mock, 'root');
});