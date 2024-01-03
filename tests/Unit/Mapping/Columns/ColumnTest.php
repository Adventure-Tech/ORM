<?php

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Mappers\BackedEnumMapper;
use AdventureTech\ORM\Mapping\Mappers\DatetimeMapper;
use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use AdventureTech\ORM\Mapping\Mappers\JSONMapper;
use AdventureTech\ORM\Mapping\Mappers\SimpleMapper;
use AdventureTech\ORM\Mapping\Mappers\UnitEnumMapper;
use AdventureTech\ORM\Mapping\Mappers\WithDefaultMapperMethods;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;

it('returns the correct mappers', function (string $propertyName, string $mapperClass) {
    $column = new Column();
    $property = new ReflectionProperty(MapperTestClass::class, $propertyName);
    $mapper = $column->getMapper($property);
    expect($mapper)->toBeInstanceOf($mapperClass);
})->with([
    ['unitEnumProperty', UnitEnumMapper::class],
    ['backedEnumProperty', BackedEnumMapper::class],
    ['boolProperty', DefaultMapper::class],
    ['stringProperty', DefaultMapper::class],
    ['intProperty', DefaultMapper::class],
    ['datetimeProperty', DatetimeMapper::class],
    ['jsonProperty', JSONMapper::class],
]);

it('correctly infers the DB column name from the property name', function () {
    $column = new Column();
    $property = new ReflectionProperty(MapperTestClass::class, 'boolProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['bool_property']);
});

it('allows the DB column name to be customized', function () {
    $column = new Column(name: 'custom_column_name');
    $property = new ReflectionProperty(MapperTestClass::class, 'boolProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['custom_column_name']);
});

it('allows a custom SimpleMapper to be specified', function () {
    $mapper = new class ('') implements SimpleMapper {
        use WithDefaultMapperMethods;

        public function serialize(mixed $value): array
        {
            return [];
        }

        public function deserialize(stdClass $item, LocalAliasingManager $aliasingManager): mixed
        {
            return null;
        }
    };
    $column = new Column(mapper: $mapper::class);
    $property = new ReflectionProperty(MapperTestClass::class, 'boolProperty');

    expect($column->getMapper($property))->toBeInstanceOf($mapper::class);
});
