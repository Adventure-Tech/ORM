<?php

use AdventureTech\ORM\Mapping\Columns\IntColumn;
use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;

test('The int column returns the default mapper', function () {
    $column = new IntColumn();
    $property = new ReflectionProperty(MapperTestClass::class, 'intProperty');
    $mapper = $column->getMapper($property);

    expect($mapper)->toBeInstanceOf(DefaultMapper::class);
});

test('The int column correctly infers the DB column name from the property name', function () {
    $column = new IntColumn();
    $property = new ReflectionProperty(MapperTestClass::class, 'intProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['int_property']);
});

test('The int column allows the DB column name to be customized', function () {
    $column = new IntColumn('custom_column_name');
    $property = new ReflectionProperty(MapperTestClass::class, 'intProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['custom_column_name']);
});
