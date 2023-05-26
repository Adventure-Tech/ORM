<?php

use AdventureTech\ORM\Mapping\Columns\DatetimeColumn;
use AdventureTech\ORM\Mapping\Mappers\DatetimeMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;

test('The datetime column returns a datetime mapper', function () {
    $column = new DatetimeColumn();
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = $column->getMapper($property);

    expect($mapper)->toBeInstanceOf(DatetimeMapper::class);
});

test('The datetime column correctly infers the DB column name from the property name', function () {
    $column = new DatetimeColumn();
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['datetime_property']);
});

test('The datetime column allows the DB column name to be customized', function () {
    $column = new DatetimeColumn('custom_column_name');
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['custom_column_name']);
});
