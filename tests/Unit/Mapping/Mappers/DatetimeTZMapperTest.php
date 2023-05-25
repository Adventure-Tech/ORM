<?php

namespace AdventureTech\ORM\Tests\Unit\Mapping\Mappers;

use AdventureTech\ORM\Mapping\Mappers\DatetimeTZMapper;
use Carbon\CarbonImmutable;
use ReflectionProperty;
use stdClass;

class DatetimeTZMapperTest
{
    public ?CarbonImmutable $foo;
}

test('The datetimetz mapper exposes the property name', function () {
    $property = new ReflectionProperty(DatetimeTZMapperTest::class, 'foo');
    $mapper = new DatetimeTZMapper('datetime_db_column', 'tz_db_column', $property);
    expect($mapper->getPropertyName())->toBe('foo');
});

test('The datetimetz mapper has a single column', function () {
    $property = new ReflectionProperty(DatetimeTZMapperTest::class, 'foo');
    $mapper = new DatetimeTZMapper('datetime_db_column', 'tz_db_column', $property);
    expect($mapper->getColumnNames())->toEqualCanonicalizing(['datetime_db_column', 'tz_db_column']);
});

test('The datetimetz mapper can check if its property is set on a given entity instance', function (
    DatetimeTZMapperTest $entity,
    bool $isInitialized
) {
    $property = new ReflectionProperty(DatetimeTZMapperTest::class, 'foo');
    $mapper = new DatetimeTZMapper('datetime_db_column', 'tz_db_column', $property);
    expect($mapper->isInitialized($entity))->toBe($isInitialized);
})->with([
    'not initialized' => [fn() => new DatetimeTZMapperTest(), false],
    'null' => [
        function () {
            $entity = new DatetimeTZMapperTest();
            $entity->foo = null;
            return $entity;
        }, true,
    ],
    'carbon instance' => [
        function () {
            $entity = new DatetimeTZMapperTest();
            $entity->foo = CarbonImmutable::now();
            return $entity;
        },
        true,
    ],
]);

test('The datetimetz mapper can serialize an entity', function (
    DatetimeTZMapperTest $entity,
    array $expected
) {
    $property = new ReflectionProperty(DatetimeTZMapperTest::class, 'foo');
    $mapper = new DatetimeTZMapper('datetime_db_column', 'tz_db_column', $property);
    expect($mapper->serialize($entity))
        ->toEqualCanonicalizing($expected);
})->with([
//    'not initialized' => [fn() => new DatetimeTZMapperTest(), []],
    'null' => [
        function () {
            $entity = new DatetimeTZMapperTest();
            $entity->foo = null;
            return $entity;
        },
        ['datetime_db_column' => null, 'tz_db_column' => null],
    ],
    'carbon instance' => [
        function () {
            $entity = new DatetimeTZMapperTest();
            $entity->foo = CarbonImmutable::parse('2023-01-01 12:00')->setTimezone('Europe/Oslo');
            return $entity;
        },
        ['datetime_db_column' => '2023-01-01T13:00:00+01:00', 'tz_db_column' => 'Europe/Oslo'],
    ],
]);

test('The datetimetz mapper can deserialize an item with a null value', function (
    stdClass $item,
    string $alias,
) {
    $property = new ReflectionProperty(DatetimeTZMapperTest::class, 'foo');
    $mapper = new DatetimeTZMapper('datetime_db_column', 'tz_db_column', $property);
    expect($mapper->deserialize($item, $alias))->toBeNull();
})->with([
    'without alias (both null)' => [(object) ['datetime_db_column' => null, 'tz_db_column' => null], ''],
    'without alias (datetime column null)' => [(object) ['datetime_db_column' => null, 'tz_db_column' => 'Europe/Oslo'], ''],
    'without alias (timezone null)' => [(object) ['datetime_db_column' => '2023-01-01T12:00:00+01:00', 'tz_db_column' => null], ''],
    'with alias (both null)' => [(object) ['aliasdatetime_db_column' => null, 'aliastz_db_column' => null], 'alias'],
]);
// TODO: decide what happens if only timezone is null

test('The datetimetz mapper can deserialize an item with a non-null value', function (
    stdClass $item,
    string $alias,
    string $iso8601String
) {
    $property = new ReflectionProperty(DatetimeTZMapperTest::class, 'foo');
    $mapper = new DatetimeTZMapper('datetime_db_column', 'tz_db_column', $property);
    expect($mapper->deserialize($item, $alias))
        ->toBeInstanceOf(CarbonImmutable::class)
        ->toIso8601String()->toBe($iso8601String);
})->with([
    'without alias' => [(object) ['datetime_db_column' => '2023-01-01T12:00:00+00:00', 'tz_db_column' => 'UTC'], '', '2023-01-01T12:00:00+00:00'],
    'with alias' => [(object) ['aliasdatetime_db_column' => '2023-01-01T12:00:00+01:00', 'aliastz_db_column' => 'UTC'], 'alias', '2023-01-01T11:00:00+00:00'],
    'with non-UTC timezone' => [(object) ['datetime_db_column' => '2023-01-01T12:00:00+00:00', 'tz_db_column' => 'Europe/Oslo'], '', '2023-01-01T13:00:00+01:00'],
]);
