<?php

use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Mappers\DatetimeMapper;
use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use AdventureTech\ORM\Mapping\Mappers\JSONMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;

test('The column annotation returns the default mapper for a bool property', function () {
    $column = new Column();
    $property = new ReflectionProperty(MapperTestClass::class, 'boolProperty');
    $mapper = $column->getMapper($property);

    expect($mapper)->toBeInstanceOf(DefaultMapper::class);
});

test('The column annotation returns the default mapper for a string property', function () {
    $column = new Column();
    $property = new ReflectionProperty(MapperTestClass::class, 'stringProperty');
    $mapper = $column->getMapper($property);

    expect($mapper)->toBeInstanceOf(DefaultMapper::class);
});

test('The column annotation returns the default mapper for an int property', function () {
    $column = new Column();
    $property = new ReflectionProperty(MapperTestClass::class, 'intProperty');
    $mapper = $column->getMapper($property);

    expect($mapper)->toBeInstanceOf(DefaultMapper::class);
});

test('The column annotation returns the datetime mapper for an CarbonImmutable property', function () {
    $column = new Column();
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = $column->getMapper($property);

    expect($mapper)->toBeInstanceOf(DatetimeMapper::class);
});

test('The column annotation returns the json mapper for an array property', function () {
    $column = new Column();
    $property = new ReflectionProperty(MapperTestClass::class, 'jsonProperty');
    $mapper = $column->getMapper($property);

    expect($mapper)->toBeInstanceOf(JSONMapper::class);
});

test('The column annotation correctly infers the DB column name from the property name', function () {
    $column = new Column();
    $property = new ReflectionProperty(MapperTestClass::class, 'boolProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['bool_property']);
});

test('The column annotation allows the DB column name to be customized', function () {
    $column = new Column('custom_column_name');
    $property = new ReflectionProperty(MapperTestClass::class, 'boolProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['custom_column_name']);
});