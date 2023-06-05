<?php

use AdventureTech\ORM\DefaultNamingService;

test('Service can convert class name to table name', function () {
    expect(DefaultNamingService::tableFromClass('\Some\Namespace\ClassName'))->toBe('class_names');
});

test('Service can convert property name to column name', function () {
    expect(DefaultNamingService::columnFromProperty('MyFooProperty'))->toBe('my_foo_property');
});

test('Service can convert class name to foreign key', function () {
    expect(DefaultNamingService::foreignKeyFromClass('\Some\Namespace\ClassName'))->toBe('class_name_id');
});
