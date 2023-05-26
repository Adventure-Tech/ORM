<?php

use AdventureTech\ORM\Exceptions\NotInitializedException;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Tests\TestClasses\MapperTestClass;

test('The table name is correctly inferred from class name', function () {
    $entity = new Entity();
    expect($entity->getTable(MapperTestClass::class))->toBe('mapper_test_classes');
});

test('The table name can be customised via constructor', function () {
    $entity = new Entity('table_name');
    expect($entity->getTable(MapperTestClass::class))->toBe('table_name');
});

test('The repository class string is null if not set in constructor', function () {
    $entity = new Entity();
    expect($entity->getRepository())->toBeNull();
});

test('The repository can be set in constructor', function () {
    $entity = new Entity(null, 'Repository');
    expect($entity->getRepository())->toBe('Repository');
});
