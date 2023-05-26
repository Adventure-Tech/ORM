<?php

use AdventureTech\ORM\Mapping\Columns\JSONColumn;
use AdventureTech\ORM\Mapping\Mappers\JSONMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;

test('The json column returns a json mapper', function () {
    $column = new JSONColumn();
    $property = new ReflectionProperty(MapperTestClass::class, 'jsonProperty');
    $mapper = $column->getMapper($property);

    expect($mapper)->toBeInstanceOf(JSONMapper::class);
});

test('The json column correctly infers the DB column name from the property name', function () {
    $column = new JSONColumn();
    $property = new ReflectionProperty(MapperTestClass::class, 'jsonProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['json_property']);
});

test('The json column allows the DB column name to be customized', function () {
    $column = new JSONColumn('custom_column_name');
    $property = new ReflectionProperty(MapperTestClass::class, 'jsonProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing(['custom_column_name']);
});
