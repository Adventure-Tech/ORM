<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Mappers;

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Mapping\Mappers\DatetimeMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;
use Carbon\CarbonImmutable;
use Mockery;
use ReflectionProperty;
use stdClass;

test('The datetime mapper identifies its type as CarbonImmutable', function (DatetimeMapper $mapper) {
    expect($mapper->getPropertyType())->toBe(CarbonImmutable::class);
})->with('mapper');

test('The datetime mapper has a single column', function (DatetimeMapper $mapper) {
    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['db_column_name']);
})->with('mapper');

test('The datetime mapper can serialize an entity', function (
    DatetimeMapper $mapper,
    ?CarbonImmutable $value,
    array $expected
) {
    expect($mapper->serialize($value))
        ->toBeArray()
        ->toEqualCanonicalizing($expected);
})
    ->with('mapper')
    ->with([
        'null' => [
            null,
            ['db_column_name' => null],
        ],
        'carbon instance' => [
            CarbonImmutable::parse('2023-01-01 12:00'),
            ['db_column_name' => '2023-01-01T12:00:00+00:00'],
        ],
    ]);

test('The datetime mapper can deserialize an item with a null value', function (
    DatetimeMapper $mapper,
    LocalAliasingManager $manager,
) {
    expect($mapper->deserialize((object) ['db_column_name' => null], $manager))->toBeNull();
})->with('mapper')->with('aliasing manager');

test('The datetime mapper can deserialize an item with a non-null value', function (
    DatetimeMapper $mapper,
    LocalAliasingManager $manager,
    stdClass $item,
    string $iso8601String
) {
    expect($mapper->deserialize($item, $manager))
        ->toBeInstanceOf(CarbonImmutable::class)
        ->toIso8601String()->toBe($iso8601String);
})
    ->with('mapper')
    ->with('aliasing manager')
    ->with([
        'with UTC timezone' => [(object) ['db_column_name' => '2023-01-01T12:00:00+00:00'], '2023-01-01T12:00:00+00:00'],
        'with non-UTC timezone' => [(object) ['db_column_name' => '2023-01-01T12:00:00+01:00'],'2023-01-01T12:00:00+01:00'],
    ]);



dataset('mapper', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    yield new DatetimeMapper('db_column_name', $property);
});

dataset('aliasing manager', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getSelectedColumnName')
        ->with('db_column_name', 'root')
        ->andReturn('db_column_name');
    yield new LocalAliasingManager($mock, 'root');
});
