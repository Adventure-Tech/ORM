<?php

use AdventureTech\ORM\Mapping\Columns\DatetimeTZColumnAnnotation;
use AdventureTech\ORM\Mapping\Mappers\DatetimeTZMapper;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;

test('The datetimeTZ column returns a datetimeTZ mapper', function () {
    $column = new DatetimeTZColumnAnnotation();
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = $column->getMapper($property);

    expect($mapper)->toBeInstanceOf(DatetimeTZMapper::class);
});

test('The datetimeTZ column handles the DB column names correctly', function (?string $name, ?string $tzName, array $result) {
    $column = new DatetimeTZColumnAnnotation($name, $tzName);
    $property = new ReflectionProperty(MapperTestClass::class, 'datetimeProperty');
    $mapper = $column->getMapper($property);

    expect($mapper->getColumnNames())
        ->toBeArray()
        ->toEqualCanonicalizing($result);
})->with([
    'default inferred column names' => [null, null, ['datetime_property', 'datetime_property_timezone']],
    'custom datetime column name' => ['a', null, ['a', 'a_timezone']],
    'custom timezone column name' => [null, 'b', ['datetime_property', 'b']],
    'custom column name for both datetime and timezone' => ['a', 'b', ['a', 'b']],
]);
