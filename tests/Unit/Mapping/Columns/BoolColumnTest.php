<?php

use AdventureTech\ORM\Mapping\Columns\BoolColumn;
use AdventureTech\ORM\Mapping\Mappers\DefaultMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;

test('The bool column returns the default mapper', function () {
    $column = new BoolColumn();
    $property = new ReflectionProperty(MapperTestClass::class, 'boolProperty');
    $mapper = $column->getMapper($property);

    expect($mapper)->toBeInstanceOf(DefaultMapper::class);
});

test('The bool column correctly infers the DB column name from the property name', function () {
    $column = new BoolColumn();
    $property = new ReflectionProperty(MapperTestClass::class, 'boolProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['bool_property']);
});

test('The bool column allows the DB column name to be customized', function () {
    $column = new BoolColumn('custom_column_name');
    $property = new ReflectionProperty(MapperTestClass::class, 'boolProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['custom_column_name']);
});
