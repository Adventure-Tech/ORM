<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Mappers;

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Mapping\Mappers\DatetimeTZMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;
use Carbon\CarbonImmutable;
use Mockery;
use ReflectionProperty;
use stdClass;

test('The datetimeTz mapper identifies its type as CarbonImmutable', function (DatetimeTZMapper $mapper) {
    expect($mapper->getPropertyType())->toBe(CarbonImmutable::class);
})->with('mapper');

test('The datetimeTz mapper has a single column', function (DatetimeTZMapper $mapper) {
    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['datetime_db_column', 'tz_db_column']);
})->with('mapper');

test('The datetimeTz mapper can serialize an entity', function (
    DatetimeTZMapper $mapper,
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
            ['datetime_db_column' => null, 'tz_db_column' => null],
        ],
        'carbon instance' => [
            CarbonImmutable::parse('2023-01-01 12:00')->setTimezone('Europe/Oslo'),
            ['datetime_db_column' => '2023-01-01T13:00:00+01:00', 'tz_db_column' => 'Europe/Oslo'],
        ],
    ]);

test('The datetimeTz mapper can deserialize an item with a null value', function (
    DatetimeTZMapper $mapper,
    LocalAliasingManager $manager,
    stdClass $item,
    string $alias,
) {
    expect($mapper->deserialize($item, $manager))->toBeNull();
})
    ->with('mapper')
    ->with('aliasing manager')
    ->with([
        'datetime and timezone null' => [(object) ['datetime_db_column' => null, 'tz_db_column' => null], ''],
        'datetime column null' => [(object) ['datetime_db_column' => null, 'tz_db_column' => 'Europe/Oslo'], ''],
    ]);

test('The datetimeTz mapper can deserialize an item with a non-null value', function (
    DatetimeTZMapper $mapper,
    LocalAliasingManager $manager,
    stdClass $item,
    string $alias,
    string $iso8601String
) {
    expect($mapper->deserialize($item, $manager))
        ->toBeInstanceOf(CarbonImmutable::class)
        ->toIso8601String()->toBe($iso8601String);
})
    ->with('mapper')
    ->with('aliasing manager')
    ->with([
    'basic UTC timezone' => [(object) ['datetime_db_column' => '2023-01-01T12:00:00+00:00', 'tz_db_column' => 'UTC'], '', '2023-01-01T12:00:00+00:00'],
    'with non-UTC timezone' => [(object) ['datetime_db_column' => '2023-01-01T12:00:00+00:00', 'tz_db_column' => 'Europe/Oslo'], '', '2023-01-01T13:00:00+01:00'],
    'with missing timezone' => [(object) ['datetime_db_column' => '2023-01-01T12:00:00+02:00', 'tz_db_column' => null], '', '2023-01-01T10:00:00+00:00'],
]);


dataset('mapper', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    yield new DatetimeTZMapper('datetime_db_column', 'tz_db_column', $property);
});

dataset('aliasing manager', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getSelectedColumnName')
        ->with('datetime_db_column', 'root')
        ->andReturn('datetime_db_column');
    $mock->shouldReceive('getSelectedColumnName')
        ->with('tz_db_column', 'root')
        ->andReturn('tz_db_column');
    yield new LocalAliasingManager($mock, 'root');
});
