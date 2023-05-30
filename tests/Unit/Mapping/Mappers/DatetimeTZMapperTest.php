<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Mappers;

use AdventureTech\ORM\Mapping\Mappers\DatetimeTZMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;
use Carbon\CarbonImmutable;
use ReflectionProperty;
use stdClass;

test('The datetimeTz mapper exposes the property name', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = new DatetimeTZMapper('datetime_db_column', 'tz_db_column', $property);
    expect($mapper->getPropertyName())->toBe('datetimeProperty');
});

test('The datetimeTz mapper has a single column', function () {
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = new DatetimeTZMapper('datetime_db_column', 'tz_db_column', $property);
    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['datetime_db_column', 'tz_db_column']);
});

test('The datetimeTz mapper can check if its property is set on a given entity instance', function (
    MapperTestClass $entity,
    bool $isInitialized
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = new DatetimeTZMapper('datetime_db_column', 'tz_db_column', $property);
    expect($mapper->isInitialized($entity))->toBe($isInitialized);
})->with([
    'not initialized' => [fn() => new MapperTestClass(), false],
    'null' => [
        function () {
            $entity = new MapperTestClass();
            $entity->datetimeProperty = null;
            return $entity;
        }, true,
    ],
    'carbon instance' => [
        function () {
            $entity = new MapperTestClass();
            $entity->datetimeProperty = CarbonImmutable::now();
            return $entity;
        },
        true,
    ],
]);

test('The datetimeTz mapper can serialize an entity', function (
    ?CarbonImmutable $value,
    array $expected
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = new DatetimeTZMapper('datetime_db_column', 'tz_db_column', $property);
    expect($mapper->serialize($value))
        ->toBeArray()
        ->toEqualCanonicalizing($expected);
})->with([
//    'not initialized' => [fn() => new MapperTestClass(), []],
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
    stdClass $item,
    string $alias,
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = new DatetimeTZMapper('datetime_db_column', 'tz_db_column', $property);
    expect($mapper->deserialize($item, $alias))->toBeNull();
})->with([
    'without alias (both null)' => [(object) ['datetime_db_column' => null, 'tz_db_column' => null], ''],
    'without alias (datetime column null)' => [(object) ['datetime_db_column' => null, 'tz_db_column' => 'Europe/Oslo'], ''],
    'with alias (both null)' => [(object) ['aliasdatetime_db_column' => null, 'aliastz_db_column' => null], 'alias'],
]);

test('The datetimeTz mapper can deserialize an item with a non-null value', function (
    stdClass $item,
    string $alias,
    string $iso8601String
) {
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = new DatetimeTZMapper('datetime_db_column', 'tz_db_column', $property);
    expect($mapper->deserialize($item, $alias))
        ->toBeInstanceOf(CarbonImmutable::class)
        ->toIso8601String()->toBe($iso8601String);
})->with([
    'without alias' => [(object) ['datetime_db_column' => '2023-01-01T12:00:00+00:00', 'tz_db_column' => 'UTC'], '', '2023-01-01T12:00:00+00:00'],
    'with alias' => [(object) ['aliasdatetime_db_column' => '2023-01-01T12:00:00+01:00', 'aliastz_db_column' => 'UTC'], 'alias', '2023-01-01T11:00:00+00:00'],
    'with non-UTC timezone' => [(object) ['datetime_db_column' => '2023-01-01T12:00:00+00:00', 'tz_db_column' => 'Europe/Oslo'], '', '2023-01-01T13:00:00+01:00'],
    'with missing timezone' => [(object) ['datetime_db_column' => '2023-01-01T12:00:00+02:00', 'tz_db_column' => null], '', '2023-01-01T10:00:00+00:00'],
]);
