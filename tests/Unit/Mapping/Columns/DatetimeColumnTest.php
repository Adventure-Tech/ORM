<?php

use AdventureTech\ORM\Mapping\Columns\DatetimeColumnAnnotation;
use AdventureTech\ORM\Mapping\Mappers\DatetimeMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;

test('The datetime column returns a datetime mapper', function () {
    $column = new DatetimeColumnAnnotation();
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = $column->getMapper($property);

    expect($mapper)->toBeInstanceOf(DatetimeMapper::class);
});

test('The datetime column correctly infers the DB column name from the property name', function () {
    $column = new DatetimeColumnAnnotation();
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['datetime_property']);
});

test('The datetime column allows the DB column name to be customized', function () {
    $column = new DatetimeColumnAnnotation('custom_column_name');
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['custom_column_name']);
});
