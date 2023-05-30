<?php

use AdventureTech\ORM\Mapping\Columns\StringColumnAnnotation;
use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;

test('The string column returns the default mapper', function () {
    $column = new StringColumnAnnotation();
    $property = new ReflectionProperty(MapperTestClass::class, 'stringProperty');
    $mapper = $column->getMapper($property);

    expect($mapper)->toBeInstanceOf(DefaultMapper::class);
});

test('The string column correctly infers the DB column name from the property name', function () {
    $column = new StringColumnAnnotation();
    $property = new ReflectionProperty(MapperTestClass::class, 'stringProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['string_property']);
});

test('The string column allows the DB column name to be customized', function () {
    $column = new StringColumnAnnotation('custom_column_name');
    $property = new ReflectionProperty(MapperTestClass::class, 'stringProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['custom_column_name']);
});
